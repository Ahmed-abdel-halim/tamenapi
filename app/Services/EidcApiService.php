<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EidcApiService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $apiKey;
    protected string $cacheKey = 'eidc_access_token_default';

    public function __construct()
    {
        $this->baseUrl = rtrim(config('eidc.base_url', 'https://in.eidc.gov.ly'), '/');
        $this->username = config('eidc.username', '');
        $this->password = config('eidc.password', '');
        $this->apiKey = config('eidc.api_key', '');
    }

    /**
     * Set credentials for a specific user
     */
    public function forUser($user): self
    {
        if ($user && !empty($user->eidc_username) && !empty($user->eidc_password)) {
            $this->username = $user->eidc_username;
            $this->password = $user->eidc_password;
            $this->cacheKey = 'eidc_access_token_' . $user->id;
            Log::info("EIDC: Set credentials for User ID: {$user->id} ({$this->username})");
        } else {
            // Default system account or fallback if user credentials missing
            if ($user) {
                Log::warning("EIDC: User ID {$user->id} has NO EIDC credentials. Falling back to default system account.");
            }
            $this->username = config('eidc.username', '');
            $this->password = config('eidc.password', '');
            $this->cacheKey = 'eidc_access_token_default';
        }
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    // ─── Authentication ────────────────────────────────────────────────────────

    /**
     * Get cached token or fetch a new one
     */
    protected function getToken(): string
    {
        $cached = Cache::get($this->cacheKey);
        if ($cached) {
            return $cached;
        }
        return $this->fetchToken();
    }

    /**
     * Fetch fresh token from EIDC
     */
    protected function fetchToken(): string
    {
        Log::info('EIDC: Fetching token for user: ' . substr($this->username, 0, 3) . '***');

        $response = Http::timeout(30)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key'    => $this->apiKey,
            ])
            ->post("{$this->baseUrl}/api/auth/token", [
                'username' => $this->username,
                'password' => $this->password,
            ]);

        if (!$response->successful()) {
            Log::error('EIDC Auth Failed!', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception('فشل الدخول لنظام الهيئة. تأكد من الإيميل وكلمة المرور والـ API Key. الحالة: ' . $response->status());
        }

        $data = $response->json();
        // The token might be in 'token' or 'accessToken' depending on the EIDC version
        $token = $data['token'] ?? $data['accessToken'] ?? null;

        if (!$token) {
            throw new \Exception('لم يتم استلام توكن من نظام الهيئة');
        }

        // Cache token (default 10 hours if expiresIn not provided)
        $ttl = ($data['expiresIn'] ?? 36000);
        Cache::put($this->cacheKey, $token, max(60, $ttl - 300));

        return $token;
    }

    /**
     * Force clear token cache
     */
    public function clearTokenCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    // ─── Core Request ──────────────────────────────────────────────────────────

    /**
     * Make an authenticated HTTP request with auto-retry on 401
     */
    protected function request(string $method, string $endpoint, array $payload = []): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $makeRequest = function (string $token) use ($method, $url, $payload) {
            $http = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
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

            if ($response->status() === 401) {
                Log::warning('EIDC: Token expired, refreshing...');
                $this->clearTokenCache();
                $response = $makeRequest($this->fetchToken());
            }

            // Handle 429 Too Many Requests: wait and retry once
            if ($response->status() === 429) {
                Log::warning("EIDC: Rate limited (429) on {$endpoint}, retrying after 3 seconds...");
                sleep(3);
                $response = $makeRequest($this->getToken());

                // If still 429 after retry, wait longer and try one more time
                if ($response->status() === 429) {
                    Log::warning("EIDC: Still rate limited (429) on {$endpoint}, retrying after 5 more seconds...");
                    sleep(5);
                    $response = $makeRequest($this->getToken());
                }
            }

            if (!$response->successful()) {
                Log::error("EIDC API Error ({$endpoint})", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }

            // Important: Some EIDC endpoints return text/plain but it's actually JSON
            $data = $response->json();
            if (!is_array($data)) {
                $decoded = json_decode($response->body(), true);
                if (is_array($decoded)) {
                    $data = $decoded;
                } else {
                    $data = ['message' => $decoded ?? (string) $response->body()];
                }
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
     * Returns list of vehicle types (e.g., sedan, truck, motorcycle...)
     */
    public function getVehicleTypes(): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/get-type-vehicles');
        return $result['data'] ?? [];
    }

    /**
     * GET /api/insurances/compulsory/get-spec-vehicles?typeId={typeId}
     * Returns specific subtypes for a given vehicle type
     */
    public function getVehicleSpecs(string $typeId): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/get-spec-vehicles', [
            'typeId' => $typeId,
        ]);
        return $result['data'] ?? [];
    }

    /**
     * GET /api/insurances/compulsory/get-detail-vehicles?typeId={typeId}
     * Returns detailed vehicle options for a given type
     */
    public function getVehicleDetails(string $typeId): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/get-detail-vehicles', [
            'typeId' => $typeId,
        ]);
        return $result['data'] ?? [];
    }

    // ─── Policy Operations ─────────────────────────────────────────────────────

    /**
     * POST /api/insurances/compulsory/inquiry
     * Get pricing and validation before creating a policy
     */
    public function inquiryPolicy(array $data): array
    {
        $result = $this->request('post', '/api/insurances/compulsory/inquiry', $data);
        return $result['data'] ?? [];
    }

    /**
     * POST /api/insurances/compulsory/create
     * Create a compulsory insurance policy on EIDC system
     *
     * Returns: { success, message, transactionCode, pdfUrl, warnings }
     */
    public function createPolicy(array $data): array
    {
        $result = $this->request('post', '/api/insurances/compulsory/create', $data);
        return $result['data'] ?? [];
    }

    /**
     * GET /api/insurances/compulsory/policies
     * List policies with pagination and filters
     */
    public function getPolicies(array $params = []): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/policies', $params);
        return $result['data'] ?? [];
    }

    /**
     * POST /api/insurances/compulsory/cancel
     * Cancel a policy by ID with a reason
     *
     * Returns: { success, message, replacementSerialCode }
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
     * Update insured person details on an existing policy
     *
     * @param string $policyId (GUID)
     * @param array $data
     * @return array { success, message, updatedAt }
     */
    public function updateInsured(string $policyId, array $data): array
    {
        $result = $this->request('patch', "/api/insurances/compulsory/{$policyId}/insured", $data);
        return $result['data'] ?? [];
    }

    /**
     * Get policy GUID and updatedAt by its transactionCode (policyNo)
     *
     * @param string $policyNo
     * @return array|null [id, updatedAt]
     */
    public function getPolicyDataByNumber(string $policyNo): ?array
    {
        $result = $this->getPolicies(['searchTerm' => $policyNo]);
        $items = $result['items'] ?? [];

        foreach ($items as $item) {
            if (($item['policyNo'] ?? '') === $policyNo) {
                return [
                    'id' => $item['id'] ?? null,
                    'updatedAt' => $item['updatedAt'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Get policy GUID by its transactionCode (policyNo)
     *
     * @param string $policyNo
     * @return string|null
     */
    public function getPolicyIdByNumber(string $policyNo): ?string
    {
        $data = $this->getPolicyDataByNumber($policyNo);
        return $data['id'] ?? null;
    }

    /**
     * GET /api/insurances/compulsory/serial-codes/stats
     * Get statistics for serial codes (total, used, unused, active, cancelled)
     */
    public function getSerialCodeStats(): array
    {
        $result = $this->request('get', '/api/insurances/compulsory/serial-codes/stats');
        return $result['data'] ?? [];
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Map our license_purpose values to EIDC purposeLicense values
     */
    public static function mapPurposeLicense(string $licensePurpose): string
    {
        return match (true) {
            str_contains($licensePurpose, 'Private') || str_contains($licensePurpose, 'خاصة')   => 'خاصة',
            str_contains($licensePurpose, 'Public')  || str_contains($licensePurpose, 'عامة')   => 'عامة',
            str_contains($licensePurpose, 'Transport') || str_contains($licensePurpose, 'نقل')  => 'نقل',
            str_contains($licensePurpose, 'Agricultural') || str_contains($licensePurpose, 'زراعي') => 'زراعي',
            str_contains($licensePurpose, 'Industrial') || str_contains($licensePurpose, 'صناعي') => 'صناعي',
            default => $licensePurpose,
        };
    }

    /**
     * Map our duration string to number of days for EIDC dayOfCarType
     * بناءً على خيارات منظومة الهيئة:
     * - تأمين سنوي -> 365
     * - تأمين من شهرين إلى 3 أشهر -> 90
     * - تأمين من شهر إلى شهرين -> 60
     * - تأمين من 15 يوم إلى شهر -> 30
     * - تأمين من 1 يوم إلى 15 يوم -> 15
     */
    public static function mapDurationToDays(string $duration): int
    {
        return match (true) {
            str_contains($duration, 'سنتين') || str_contains($duration, '730') => 730,
            str_contains($duration, '3 أشهر') || str_contains($duration, '90')    => 90,
            str_contains($duration, 'شهرين') || str_contains($duration, '60')     => 60,
            str_contains($duration, 'شهر') || str_contains($duration, '30')       => 30,
            str_contains($duration, '15 يوم') || str_contains($duration, '15')    => 15,
            default                                                               => 365, // تأمين سنوي
        };
    }
}
