<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * EIDC (هيئة الإشراف على التأمين) API Service
 *
 * Authentication model (per official docs):
 *   - ONE API key per company → exchanged for a short-lived JWT via POST /api/auth/token
 *   - Token is cached and shared across all requests (no per-agent credentials needed)
 *   - Individual agents are identified via issuerName / issuerType fields on /create
 *
 * Base URL: https://in.eidc.gov.ly
 */
class EidcApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    // Single company token cache key
    protected const TOKEN_CACHE_KEY = 'eidc_company_access_token';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('eidc.base_url', 'https://in.eidc.gov.ly'), '/');
        $this->apiKey  = config('eidc.api_key', '');
    }

    /**
     * Kept for backward compatibility — no longer needed since all agents
     * use the single company API key. Safe to call, does nothing meaningful.
     */
    public function forUser($user): self
    {
        return $this;
    }

    public function getUsername(): string
    {
        return config('eidc.username', '');
    }

    // ─── Authentication ────────────────────────────────────────────────────────

    /**
     * Return cached company JWT or fetch a fresh one.
     */
    protected function getToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached) {
            return $cached;
        }
        return $this->fetchToken();
    }

    /**
     * Exchange the company API key for a short-lived JWT.
     *
     * Per docs: POST /api/auth/token requires only the X-API-Key header.
     * No username/password body needed.
     */
    protected function fetchToken(): string
    {
        Log::info('EIDC: Fetching company token via API key...');

        $response = Http::timeout(30)
            ->withHeaders([
                'Accept'    => 'application/json',
                'X-API-Key' => $this->apiKey,
            ])
            ->post("{$this->baseUrl}/api/auth/token");

        if (!$response->successful()) {
            Log::error('EIDC: Token fetch failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('فشل الحصول على توكن الهيئة. تأكد من صحة EIDC_API_KEY في ملف .env. الحالة: ' . $response->status());
        }

        $data  = $response->json();
        $token = $data['accessToken'] ?? $data['token'] ?? null;

        if (!$token) {
            throw new \Exception('لم يتم استلام توكن من نظام الهيئة');
        }

        // Cache for expiresIn seconds, minus 5-minute safety margin
        $ttl = ($data['expiresIn'] ?? 3600);
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, $ttl - 300));

        Log::info('EIDC: Company token cached', ['expiresIn' => $ttl]);

        return $token;
    }

    /**
     * Force clear the company token cache (e.g. after 401 response).
     */
    public function clearTokenCache(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    // ─── Core Request ──────────────────────────────────────────────────────────

    /**
     * Make an authenticated HTTP request with auto-retry on 401 (expired token).
     */
    protected function request(string $method, string $endpoint, array $payload = []): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $makeRequest = function (string $token) use ($method, $url, $payload) {
            $http = Http::timeout(30)->withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'X-API-Key'     => $this->apiKey,
            ]);

            return match (strtolower($method)) {
                'get'   => $http->get($url, $payload),
                'post'  => $http->post($url, $payload),
                'patch' => $http->patch($url, $payload),
                'put'   => $http->put($url, $payload),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };
        };

        try {
            Log::info("EIDC Request: {$method} {$url}");
            $response = $makeRequest($this->getToken());

            // Auto-refresh on 401 (expired token)
            if ($response->status() === 401) {
                Log::warning('EIDC: Token expired, refreshing...');
                $this->clearTokenCache();
                $response = $makeRequest($this->fetchToken());
            }

            // Retry on 429 (rate limit) per docs: wait Retry-After seconds
            if ($response->status() === 429) {
                $retryAfter = (int) ($response->header('Retry-After') ?: 5);
                Log::warning("EIDC: Rate limited (429) on {$endpoint}, waiting {$retryAfter}s...");
                sleep(min($retryAfter, 15));
                $response = $makeRequest($this->getToken());

                if ($response->status() === 429) {
                    Log::warning("EIDC: Still rate limited (429) on {$endpoint}, waiting 15 more seconds...");
                    sleep(15);
                    $response = $makeRequest($this->getToken());
                }
            }

            // Parse response body
            $data = $response->json();
            if (!is_array($data)) {
                $decoded = json_decode($response->body(), true);
                $data = is_array($decoded) ? $decoded : ['message' => (string) $response->body()];
            }

            // Flatten schema validation errors into a single message
            if (!$response->successful() && isset($data['errors']) && is_array($data['errors'])) {
                $msgs = [];
                foreach ($data['errors'] as $field => $messages) {
                    $msgs[] = is_array($messages) ? implode(', ', $messages) : $messages;
                }
                if ($msgs) {
                    $data['message'] = implode(' | ', $msgs);
                }
            }

            if (!$response->successful()) {
                Log::error("EIDC API Error ({$endpoint})", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            Log::info('EIDC: API Response', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'success'  => $response->successful(),
            ]);

            return [
                'status'     => $response->status(),
                'data'       => $data ?? [],
                'successful' => $response->successful(),
            ];

        } catch (\Exception $e) {
            Log::error('EIDC: Request exception', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─── Vehicle Lookups ───────────────────────────────────────────────────────

    /**
     * GET /api/insurances/compulsory/get-type-vehicles
     * Cached for 1 hour (catalogue rarely changes).
     */
    public function getVehicleTypes(): array
    {
        return Cache::remember('eidc_vehicle_types_v2', 3600, function () {
            $result = $this->request('get', '/api/insurances/compulsory/get-type-vehicles');
            return $result['data'] ?? [];
        });
    }

    /**
     * GET /api/insurances/compulsory/get-spec-vehicles?typeId={typeId}
     */
    public function getVehicleSpecs(string $typeId): array
    {
        return Cache::remember("eidc_vehicle_specs_v2_{$typeId}", 3600, function () use ($typeId) {
            $result = $this->request('get', '/api/insurances/compulsory/get-spec-vehicles', [
                'typeId' => $typeId,
            ]);
            return $result['data'] ?? [];
        });
    }

    /**
     * GET /api/insurances/compulsory/get-detail-vehicles?typeId={typeId}
     */
    public function getVehicleDetails(string $typeId): array
    {
        return Cache::remember("eidc_vehicle_details_v2_{$typeId}", 3600, function () use ($typeId) {
            $result = $this->request('get', '/api/insurances/compulsory/get-detail-vehicles', [
                'typeId' => $typeId,
            ]);
            return $result['data'] ?? [];
        });
    }

    // ─── Policy Operations ─────────────────────────────────────────────────────

    /**
     * POST /api/insurances/compulsory/inquiry
     * Read-only premium preview. No serial code allocated, no PDF generated.
     */
    public function inquiryPolicy(array $data): array
    {
        $result = $this->request('post', '/api/insurances/compulsory/inquiry', $data);
        return $result['data'] ?? [];
    }

    /**
     * POST /api/insurances/compulsory/create
     * Create a compulsory insurance policy.
     *
     * Pass issuerName (agent's name) and issuerType (3 = Agent) so the PDF
     * shows the correct agent name instead of the company account name.
     */
    public function createPolicy(array $data): array
    {
        $result = $this->request('post', '/api/insurances/compulsory/create', $data);
        return $result['data'] ?? [];
    }

    /**
     * GET /api/insurances/compulsory/policies
     */
    public function getPolicies(array $params = []): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/policies', $params);
        return $result['data'] ?? [];
    }

    /**
     * POST /api/insurances/compulsory/cancel
     */
    public function cancelPolicy(string $policyId, string $reason): array
    {
        $result = $this->request('post', '/api/insurances/compulsory/cancel', [
            'policyId' => $policyId,
            'reason'   => $reason,
        ]);
        return $result['data'] ?? [];
    }

    /**
     * PATCH /api/insurances/compulsory/{policyId}/insured
     */
    public function updateInsured(string $policyId, array $data): array
    {
        $result = $this->request('patch', "/api/insurances/compulsory/{$policyId}/insured", $data);
        return $result['data'] ?? [];
    }

    /**
     * Find a policy's internal GUID and updatedAt by its printed policyNo.
     */
    public function getPolicyDataByNumber(string $policyNo): ?array
    {
        $result = $this->getPolicies(['searchTerm' => $policyNo]);
        $items  = $result['items'] ?? [];

        foreach ($items as $item) {
            if (($item['policyNo'] ?? '') === $policyNo) {
                return [
                    'id'        => $item['id'] ?? null,
                    'updatedAt' => $item['updatedAt'] ?? null,
                ];
            }
        }

        return null;
    }

    public function getPolicyIdByNumber(string $policyNo): ?string
    {
        return ($this->getPolicyDataByNumber($policyNo))['id'] ?? null;
    }

    /**
     * GET /api/insurances/compulsory/serial-codes/stats
     */
    public function getSerialCodeStats(): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/serial-codes/stats');
        return $result['data'] ?? [];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public static function mapPurposeLicense(string $licensePurpose): string
    {
        return match (true) {
            str_contains($licensePurpose, 'Private') || str_contains($licensePurpose, 'خاصة')    => 'خاصة',
            str_contains($licensePurpose, 'Public')  || str_contains($licensePurpose, 'عامة')    => 'عامة',
            str_contains($licensePurpose, 'Transport') || str_contains($licensePurpose, 'نقل')   => 'نقل',
            str_contains($licensePurpose, 'Agricultural') || str_contains($licensePurpose, 'زراعي') => 'زراعي',
            str_contains($licensePurpose, 'Industrial') || str_contains($licensePurpose, 'صناعي') => 'صناعي',
            default => $licensePurpose,
        };
    }

    public static function mapDurationToDays(string $duration): int
    {
        return match (true) {
            str_contains($duration, 'سنتين') || str_contains($duration, '730') => 730,
            str_contains($duration, '3 أشهر') || str_contains($duration, '90') => 90,
            str_contains($duration, 'شهرين') || str_contains($duration, '60')  => 60,
            str_contains($duration, 'شهر') || str_contains($duration, '30')    => 30,
            str_contains($duration, '15 يوم') || str_contains($duration, '15') => 15,
            default => 365,
        };
    }
}
