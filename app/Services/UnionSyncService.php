<?php

namespace App\Services;

use App\Models\InternationalInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnionSyncService
{
    protected string $lifoUsername;
    protected string $lifoPassword;
    protected string $baseUrl;

    public function __construct()
    {
        $this->lifoUsername = env('LIFO_USERNAME', 'adminmli');
        $this->lifoPassword = env('LIFO_PASSWORD', '20232024');
        $this->baseUrl = env('LIFO_BASE_URL', 'https://prodapi.lifo.ly');
    }

    /**
     * Run the synchronization process.
     */
    public function sync(): array
    {
        // Increase time and memory limits for large data sets (90k+ records)
        ini_set('memory_limit', '2048M');
        set_time_limit(900);

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        $lock = Cache::lock('union_sync_service_lock', 900); // 15 minutes lock

        if (!$lock->get()) {
            Log::info('UnionSyncService: Synchronization is already running in another process.');
            $stats['errors'][] = 'عملية المزامنة قيد التشغيل حالياً في خلفية النظام، يرجى الانتظار لحين اكتمالها.';
            return $stats;
        }

        try {
            Log::info('UnionSyncService: Starting synchronization with Union (LIFO)...');

            // 1. Fetch offices list (cached for performance)
            $officesList = $this->fetchOffices();
            if (empty($officesList)) {
                throw new \Exception('تعذر الحصول على قائمة المكاتب من خادم الاتحاد');
            }

            // 2. Fetch cards list (cached for performance, since it has 92,000+ cards)
            $cardsList = $this->fetchCards();
            if (empty($cardsList)) {
                throw new \Exception('تعذر الحصول على قائمة البطاقات من خادم الاتحاد');
            }

            // Build cards map and identify cancelled cards
            $cardsMap = [];
            $cancelledCardIds = [];
            $cancelledCardNumbers = [];
            $cancelledCardsByNumber = [];

            foreach ($cardsList as $card) {
                $status = $card['cardstautesname'] ?? '';
                $num = $card['card_number'] ?? $card['card_serial'] ?? null;

                if (!empty($card['id'])) {
                    $cardsMap[$card['id']] = $num;
                    if ($status === 'البطاقات الملغية' || $status === 'الملغية') {
                        $cancelledCardIds[$card['id']] = true;
                        if ($num) {
                            $cancelledCardNumbers[] = $num;
                            $cancelledCardsByNumber[$num] = $card;
                        }
                    }
                }
            }

            // Preload agents for office/agency mapping
            $allAgents = BranchAgent::all();
            $agentMapByOffice = [];
            foreach ($allAgents as $ag) {
                if ($ag->agency_name) $agentMapByOffice[mb_strtolower(trim($ag->agency_name))] = $ag->id;
                if ($ag->code) $agentMapByOffice[mb_strtolower(trim($ag->code))] = $ag->id;
                if ($ag->agent_name) $agentMapByOffice[mb_strtolower(trim($ag->agent_name))] = $ag->id;
            }

            // Mark existing cancelled documents in local DB as canceled (instead of deleting)
            if (!empty($cancelledCardNumbers)) {
                $updatedCount = DB::table('international_insurance_documents')
                    ->whereIn('document_number', $cancelledCardNumbers)
                    ->update([
                        'is_canceled' => 1,
                        'canceled_at' => DB::raw('IFNULL(canceled_at, NOW())'),
                        'cancel_reason' => DB::raw("IFNULL(cancel_reason, 'إلغاء البطاقة من خادم الاتحاد (LIFO)')"),
                    ]);
                Log::info("UnionSyncService: Marked {$updatedCount} documents as cancelled in local database.");

                // For any cancelled cards in LIFO list that are missing in DB, insert them mapped to their agent
                foreach ($cancelledCardsByNumber as $num => $card) {
                    $officeName = trim($card['offices'] ?? '');
                    $agentId = null;
                    if ($officeName) {
                        $lowerOffice = mb_strtolower($officeName);
                        if (isset($agentMapByOffice[$lowerOffice])) {
                            $agentId = $agentMapByOffice[$lowerOffice];
                        } else {
                            foreach ($allAgents as $ag) {
                                if (!empty($ag->code) && str_contains($lowerOffice, mb_strtolower($ag->code))) {
                                    $agentId = $ag->id;
                                    break;
                                }
                                if (!empty($ag->agency_name) && (str_contains($lowerOffice, mb_strtolower($ag->agency_name)) || str_contains(mb_strtolower($ag->agency_name), $lowerOffice))) {
                                    $agentId = $ag->id;
                                    break;
                                }
                            }
                        }
                    }

                    $exists = DB::table('international_insurance_documents')->where('document_number', $num)->exists();
                    if (!$exists) {
                        DB::table('international_insurance_documents')->insert([
                            'document_number'        => $num,
                            'external_policy_number' => $card['id'] ?? null,
                            'insured_name'           => 'بطاقة برتقالية ملغية (LIFO)',
                            'item_type'              => 'سيارات خاصة ملاكي',
                            'total'                  => 0.000,
                            'premium'                => 0.000,
                            'tax'                    => 0.000,
                            'supervision_fees'       => 0.000,
                            'issue_fees'             => 0.000,
                            'stamp'                  => 0.000,
                            'issue_date'             => !empty($card['created_at']) ? substr($card['created_at'], 0, 19) : now()->toDateTimeString(),
                            'branch_agent_id'        => $agentId,
                            'is_canceled'            => 1,
                            'canceled_at'            => !empty($card['created_at']) ? substr($card['created_at'], 0, 19) : now()->toDateTimeString(),
                            'cancel_reason'          => 'إلغاء البطاقة من خادم الاتحاد (LIFO)',
                            'created_at'             => now(),
                            'updated_at'             => now(),
                        ]);
                    } elseif ($agentId) {
                        DB::table('international_insurance_documents')
                            ->where('document_number', $num)
                            ->whereNull('branch_agent_id')
                            ->update(['branch_agent_id' => $agentId]);
                    }
                }
            }

            Log::info('UnionSyncService: Fetching reports for all ' . count($officesList) . ' offices...');

            // 4. Fetch reports for all offices + company main office
            $reports = $this->fetchReportsConcurrently($officesList);
            if (empty($reports)) {
                Log::info('UnionSyncService: No reports found on Union server.');
                return $stats;
            }

            $totalFetched = count($reports);
            Log::info("UnionSyncService: Retrieved a total of {$totalFetched} reports from Union.");

            // ★ INCREMENTAL SYNC: Filter out reports that already exist in the DB
            // This avoids re-processing 90k+ records every time
            $existingExternalIds = DB::table('international_insurance_documents')
                ->whereNotNull('external_policy_number')
                ->pluck('external_policy_number')
                ->flip()
                ->all();

            $newReports = [];
            foreach ($reports as $report) {
                $lifoId = $report['id'] ?? null;
                if ($lifoId && isset($existingExternalIds[$lifoId])) {
                    $stats['skipped']++;
                    continue; // Already in our DB, skip
                }
                $newReports[] = $report;
            }

            // Free memory from the full reports array
            unset($reports);

            if (empty($newReports)) {
                Log::info("UnionSyncService: All {$totalFetched} reports already exist locally. Nothing new to sync.");
                Cache::put('union_last_sync_at', now()->toDateTimeString(), 86400 * 30);
                return $stats;
            }

            $reports = $newReports;
            unset($newReports);

            Log::info("UnionSyncService: " . count($reports) . " new reports to process (skipped {$stats['skipped']} existing). Preloading maps...");

            // 5. Preload all local users mapping for efficient lookup
            $localUsers = User::all();
            $usernameMap = [];
            $officeToAgentMap = [];
            $officeIdToUserMap = [];

            foreach ($localUsers as $user) {
                $username = strtolower(trim($user->lifo_username ?? ''));
                $officeId = trim($user->lifo_office_id ?? '');

                if ($username) {
                    $usernameMap[$username] = $user;
                }

                if ($officeId) {
                    $officeIdToUserMap[$officeId] = $user;
                    if (!empty($user->branch_agent_id)) {
                        $officeToAgentMap[$officeId] = $user->branch_agent_id;
                    }
                }
            }

            // 6. Preload existing documents map for the remaining new reports
            // (Some new reports may match by document_number for updates)
            $existingDocs = DB::table('international_insurance_documents')
                ->select('id', 'external_policy_number', 'document_number')
                ->get();
            $existingExternalMap = [];
            $existingDocNumberMap = [];
            foreach ($existingDocs as $d) {
                if (!empty($d->external_policy_number)) {
                    $existingExternalMap[$d->external_policy_number] = $d->id;
                }
                if (!empty($d->document_number)) {
                    $existingDocNumberMap[$d->document_number] = $d->id;
                }
            }
            unset($existingDocs);

            // 7. Preload all agents
            $localAgents = BranchAgent::all();
            $agentsMap = [];
            $agentsMapById = [];
            foreach ($localAgents as $agent) {
                $agentsMap[trim(strtolower($agent->agency_name))] = $agent;
                $agentsMapById[$agent->id] = $agent;
            }

            // 8. Process reports and save to database in chunks of 5000 inside transactions
            $chunks = array_chunk($reports, 5000);
            foreach ($chunks as $chunkIndex => $chunk) {
                DB::transaction(function() use ($chunk, $cardsMap, &$cancelledCardIds, &$existingExternalMap, &$existingDocNumberMap, &$usernameMap, &$officeToAgentMap, &$officeIdToUserMap, &$agentsMap, &$agentsMapById, &$stats) {
                    foreach ($chunk as $doc) {
                        try {
                            $lifoDocId = $doc['id'] ?? null;
                            if (!$lifoDocId) {
                                $stats['failed']++;
                                continue;
                            }

                            // Skip if the card is cancelled
                            $cardsId = $doc['cards_id'] ?? null;
                            if ($cardsId && isset($cancelledCardIds[$cardsId])) {
                                $stats['skipped']++;
                                continue;
                            }

                            // Resolve card serial number
                            $cardNumber = $cardsMap[$doc['cards_id'] ?? ''] ?? $doc['policyNumber'] ?? $doc['card_number'] ?? null;
                            if (!$cardNumber) {
                                $stats['failed']++;
                                $stats['errors'][] = "التقرير رقم {$lifoDocId}: تعذر معرفة رقم البطاقة";
                                continue;
                            }

                            // Check if already exists in DB
                            $existingId = $existingExternalMap[$lifoDocId] ?? $existingDocNumberMap[$cardNumber] ?? $existingExternalMap[$cardNumber] ?? null;

                            // Prepare mapped attributes
                            $premium       = (float) ($doc['insurance_installment'] ?? 0);
                            $tax           = (float) ($doc['insurance_tax'] ?? 0);
                            $supervision   = (float) ($doc['insurance_supervision'] ?? 0);
                            $issueFees     = (float) ($doc['insurance_version'] ?? 10.000);
                            $stamp         = (float) ($doc['insurance_stamp'] ?? 0.250);
                            $total         = (float) ($doc['insurance_total'] ?? 0);
                            $dailyPremium  = (float) ($doc['insurance_installment_daily'] ?? 0);

                            $startDate     = !empty($doc['insurance_day_from']) ? substr($doc['insurance_day_from'], 0, 10) : date('Y-m-d');
                            $endDate       = !empty($doc['nsurance_day_to']) ? substr($doc['nsurance_day_to'], 0, 10) : date('Y-m-d');
                            $numberOfDays  = (int) ($doc['insurance_days_number'] ?? 1);

                            $itemType      = $this->mapClauseToItemType($doc['insurance_clauses_id'] ?? '');
                            $visitedCountry = $this->mapCountry($doc['countries_id'] ?? 0, $doc['countries']['name'] ?? null);
                            $vehicleNationality = ($doc['vehicle_nationalities']['name'] ?? '') === 'ليبية' ? 'ليبية- LBY' : ($doc['vehicle_nationalities']['name'] ?? 'ليبية- LBY');

                            $chassisNumber = $doc['chassis_number'] ?? '';
                            $plateNumber   = $doc['plate_number'] ?? '';
                            $year          = isset($doc['car_made_date']) ? (int) $doc['car_made_date'] : null;
                            $insuredName   = $doc['insurance_name'] ?? '';
                            $insuredAddress = $doc['insurance_location'] ?? '';
                            $phone         = $doc['insurance_phone'] ?? '';
                            
                            $issueDate     = !empty($doc['issuing_date']) ? $this->sanitizeIssueDate($doc['issuing_date']) : now();

                            // Find matching user and agent (Consolidate all sub-users under main Office agent)
                            $lifoOfficeId = $doc['offices_id'] ?? null;
                            $lifoUsername = $doc['office_users']['username'] ?? $doc['company_users']['username'] ?? null;

                            $assignedUserId = null;
                            $assignedAgentId = null;

                            // Match 1: Map by LIFO Office ID directly to main local agent
                            if ($lifoOfficeId) {
                                $assignedAgentId = $officeToAgentMap[$lifoOfficeId] ?? null;
                                if ($assignedAgentId) {
                                    $agent = $agentsMapById[$assignedAgentId] ?? null;
                                    $assignedUserId = $agent ? $agent->user_id : ($officeIdToUserMap[$lifoOfficeId]->id ?? null);
                                }
                            }

                            // Match 2: Fallback to username mapping (in case office ID is missing or not configured)
                            if (!$assignedAgentId && $lifoUsername) {
                                $localUser = $usernameMap[strtolower(trim($lifoUsername))] ?? null;
                                if ($localUser) {
                                    $assignedUserId = $localUser->id;
                                    $assignedAgentId = $localUser->branch_agent_id;
                                }
                            }

                            // If we resolved the agent but no specific user, default user to that agent's owner if possible
                            if (!$assignedUserId && $assignedAgentId) {
                                $agent = $agentsMapById[$assignedAgentId] ?? null;
                                if ($agent && $agent->user_id) {
                                    $assignedUserId = $agent->user_id;
                                }
                            }

                            // Match 3: If agent is still not resolved, resolve by Union Office Name or create agent
                            if (!$assignedAgentId && !empty($doc['offices']['name'])) {
                                $officeName = trim($doc['offices']['name']);
                                $officeNameKey = trim(strtolower($officeName));
                                if ($officeName !== 'لدي الشركة' && $officeName !== '') {
                                    $agent = $agentsMap[$officeNameKey] ?? null;
                                    if ($agent) {
                                        $assignedAgentId = $agent->id;
                                        $assignedUserId = $agent->user_id;
                                    } else {
                                        // Create BranchAgent automatically (we query DB inside transaction for uniqueness checks)
                                        $lastAgent = BranchAgent::where('code', 'like', 'BK%')->orderBy('id', 'desc')->first();
                                        $nextNumber = $lastAgent ? ((int) substr($lastAgent->code, 2) + 1) : 1;
                                        do {
                                            $code = 'BK' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                                            $nextNumber++;
                                        } while (BranchAgent::where('code', $code)->exists());

                                        $newAgent = BranchAgent::create([
                                            'type' => 'وكيل',
                                            'code' => $code,
                                            'agency_name' => $officeName,
                                            'agent_name' => $doc['offices']['fullname_manger'] ?? $officeName,
                                            'contract_date' => now()->toDateString(),
                                            'city' => $doc['insurance_location'] ?? 'غير محدد',
                                            'status' => 'نشط',
                                            'authorized_documents' => ['تأمين سيارات دولي'],
                                            'document_percentages' => [],
                                        ]);
                                        $assignedAgentId = $newAgent->id;
                                        $agentsMap[$officeNameKey] = $newAgent;
                                        $agentsMapById[$newAgent->id] = $newAgent;
                                    }
                                }
                            }

                            $attributes = [
                                'document_number'                  => $cardNumber,
                                'external_policy_number'           => $lifoDocId,
                                'insured_name'                     => $insuredName,
                                'insured_address'                  => $insuredAddress,
                                'phone'                            => $phone,
                                'whatsapp_number'                  => $phone,
                                'chassis_number'                   => $chassisNumber,
                                'plate_number'                     => $plateNumber,
                                'external_car_id'                  => $doc['cars_id'] ?? null,
                                'external_vehicle_nationality_id'  => $doc['vehicle_nationalities_id'] ?? null,
                                'external_country_id'              => $doc['countries_id'] ?? null,
                                'year'                             => $year,
                                'vehicle_nationality'              => $vehicleNationality,
                                'visited_country'                  => $visitedCountry,
                                'start_date'                       => $startDate,
                                'number_of_days'                   => $numberOfDays,
                                'end_date'                         => $endDate,
                                'item_type'                        => $itemType,
                                'number_of_countries'              => $doc['insurance_country_number'] ?? 1,
                                'daily_premium'                    => $dailyPremium,
                                'premium'                          => $premium,
                                'tax'                              => $tax,
                                'supervision_fees'                 => $supervision,
                                'issue_fees'                       => $issueFees,
                                'stamp'                            => $stamp,
                                'total'                            => $total,
                                'issue_date'                       => $issueDate,
                                'user_id'                          => $assignedUserId,
                                'branch_agent_id'                  => $assignedAgentId,
                            ];

                            if ($existingId) {
                                // Retrieve the old document_number before updating
                                $oldDocNumber = DB::table('international_insurance_documents')
                                    ->where('id', $existingId)
                                    ->value('document_number');

                                $attributes['updated_at'] = now();
                                DB::table('international_insurance_documents')
                                    ->where('id', $existingId)
                                    ->update($attributes);
                                $stats['updated']++;

                                // If the document number changed (e.g. from local LBY0070 to LBY/7130950),
                                // update any references in related tables to avoid broken links
                                if ($oldDocNumber && $oldDocNumber !== $cardNumber) {
                                    $tablesToUpdate = ['claims', 'document_requests', 'commissions'];
                                    foreach ($tablesToUpdate as $table) {
                                        try {
                                            DB::table($table)
                                                ->where('document_number', $oldDocNumber)
                                                ->update(['document_number' => $cardNumber]);
                                        } catch (\Exception $ex) {
                                            Log::error("UnionSyncService: Failed to update document number in {$table}. Error: " . $ex->getMessage());
                                        }
                                    }
                                }

                                // Update maps to prevent duplicate processing
                                if ($lifoDocId) {
                                    $existingExternalMap[$lifoDocId] = $existingId;
                                }
                                if ($cardNumber) {
                                    $existingDocNumberMap[$cardNumber] = $existingId;
                                }
                            } else {
                                $attributes['created_at'] = now();
                                $attributes['updated_at'] = now();
                                $newId = DB::table('international_insurance_documents')
                                    ->insertGetId($attributes);

                                // Add to maps to prevent duplicate insertion within same chunk
                                if ($lifoDocId) {
                                    $existingExternalMap[$lifoDocId] = $newId;
                                }
                                if ($cardNumber) {
                                    $existingDocNumberMap[$cardNumber] = $newId;
                                }
                                $stats['created']++;
                            }
                        } catch (\Exception $ex) {
                            Log::error("UnionSyncService: Failed to process document. Error: " . $ex->getMessage());
                            $stats['failed']++;
                            $stats['errors'][] = "الوثيقة " . ($doc['id'] ?? 'N/A') . ": " . $ex->getMessage();
                        }
                    }
                });
                Log::info("UnionSyncService: Processed chunk " . ($chunkIndex + 1) . " of " . count($chunks));
            }

            // Save last successful sync timestamp
            Cache::put('union_last_sync_at', now()->toDateTimeString(), 86400 * 30);

            Log::info("UnionSyncService: Completed. Created: {$stats['created']}, Updated: {$stats['updated']}, Skipped: {$stats['skipped']}, Failed: {$stats['failed']}");
        } catch (\Exception $e) {
            Log::error('UnionSyncService: Synchronization failed completely. Error: ' . $e->getMessage());
            $stats['errors'][] = $e->getMessage();
        } finally {
            $lock->release();
        }

        return $stats;
    }

    /**
     * Fetch offices from LIFO API.
     */
    protected function fetchOffices(): array
    {
        return Cache::remember('lifo_offices_adminmli', 86400, function () {
            try {
                $response = Http::timeout(45)
                    ->withoutVerifying()
                    ->post("{$this->baseUrl}/api/offices/all", [
                        'user_name' => $this->lifoUsername,
                        'pass_word' => $this->lifoPassword,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        return $data['data'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('UnionSyncService: fetchOffices failed. Error: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Fetch cards from LIFO API (cached under 'lifo_cards_adminmli_all' for 24h).
     */
    protected function fetchCards(): array
    {
        return Cache::remember('lifo_cards_adminmli_all', 86400, function () {
            try {
                Log::info('UnionSyncService: Cache expired. Fetching 90k+ cards list from Union API...');
                $response = Http::timeout(300)
                    ->withoutVerifying()
                    ->post("{$this->baseUrl}/api/cards/all", [
                        'user_name' => $this->lifoUsername,
                        'pass_word' => $this->lifoPassword,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        return $data['data'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('UnionSyncService: fetchCards failed. Error: ' . $e->getMessage());
            }
            return [];
        });
    }

    /**
     * Fetch reports concurrently from LIFO API for company + all active offices.
     */
    protected function fetchReportsConcurrently(array $officesList): array
    {
        $allReports = [];
        $largeOfficeIds = [1404, 1897, 1838, 1976, 1809, 1501, 2064, 1508, 1513, 1557, 1491, 1792, 1881, 1899];

        // 1. Fetch large offices sequentially with high timeout
        foreach ($officesList as $office) {
            $officeId = (int)$office['id'];
            if (in_array($officeId, $largeOfficeIds)) {
                try {
                    Log::info("UnionSyncService: Fetching large office reports for ID {$officeId} sequentially...");
                    $response = Http::timeout(300)->withoutVerifying()->post("{$this->baseUrl}/api/report/byoffice/{$officeId}", [
                        'user_name' => $this->lifoUsername,
                        'pass_word' => $this->lifoPassword,
                    ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['code']) && $data['code'] === 1) {
                            $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                            foreach ($list as $doc) {
                                $doc['offices_id'] = $officeId;
                                $doc['offices'] = $doc['offices'] ?? ['id' => $office['id'], 'name' => $office['name']];
                                $allReports[] = $doc;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("UnionSyncService: Failed to fetch large office {$officeId} sequentially. Error: " . $e->getMessage());
                }
            }
        }

        // Filter out large offices for batched concurrent pooling
        $smallOffices = array_filter($officesList, function($office) use ($largeOfficeIds) {
            return !in_array((int)$office['id'], $largeOfficeIds);
        });

        // 2. Fetch company head office reports
        try {
            Log::info("UnionSyncService: Fetching company head office reports...");
            $response = Http::timeout(180)->withoutVerifying()->post("{$this->baseUrl}/api/report/all", [
                'user_name' => $this->lifoUsername,
                'pass_word' => $this->lifoPassword,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] === 1) {
                    $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                    foreach ($list as $doc) {
                        $allReports[] = $doc;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("UnionSyncService: Failed to fetch company head office reports. Error: " . $e->getMessage());
        }

        // 3. Fetch small offices in batches of 35 to prevent overloading the LIFO API
        $officeChunks = array_chunk($smallOffices, 35);
        foreach ($officeChunks as $chunkIdx => $chunk) {
            try {
                Log::info("UnionSyncService: Fetching small offices batch " . ($chunkIdx + 1) . " of " . count($officeChunks) . " concurrently...");
                $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($chunk) {
                    $requests = [];
                    foreach ($chunk as $office) {
                        $officeId = $office['id'];
                        $requests[] = $pool->as('office_' . $officeId)->timeout(90)->withoutVerifying()->post("{$this->baseUrl}/api/report/byoffice/{$officeId}", [
                            'user_name' => $this->lifoUsername,
                            'pass_word' => $this->lifoPassword,
                        ]);
                    }
                    return $requests;
                });

                foreach ($chunk as $office) {
                    $officeId = $office['id'];
                    $key = 'office_' . $officeId;
                    if (isset($responses[$key]) && $responses[$key] instanceof \Illuminate\Http\Client\Response && $responses[$key]->successful()) {
                        $data = $responses[$key]->json();
                        if (isset($data['code']) && $data['code'] === 1) {
                            $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                            foreach ($list as $doc) {
                                $doc['offices_id'] = $officeId;
                                $doc['offices'] = $doc['offices'] ?? ['id' => $office['id'], 'name' => $office['name']];
                                $allReports[] = $doc;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("UnionSyncService: Batch concurrent fetch failed. Error: " . $e->getMessage());
            }
        }

        return $allReports;
    }

    /**
     * Map LIFO Clause Code to local enum item type.
     */
    protected function mapClauseToItemType(?string $clause): string
    {
        $clause = strtoupper(trim($clause));
        $map = [
            'PV' => 'سيارات خاصة ملاكي',
            '1'  => 'سيارات خاصة ملاكي',
            'MC' => 'دراجة نارية',
            'DT' => 'سيارة تعليم قيادة',
            'AM' => 'سيارة اسعاف',
            'HE' => 'سيارة نقل الموتى',
            'TR' => 'مقطورة',
            'CV' => 'السيارات التجارية',
            'TC' => 'الجرارات',
            'GV' => 'سيارات نقل بضائع',
            'BS' => 'سيارات الركوبة الحافلات',
        ];
        return $map[$clause] ?? 'سيارات خاصة ملاكي';
    }

    /**
     * Map LIFO Country Name/ID to local enum visited country.
     */
    protected function mapCountry($countryId, ?string $countryName): string
    {
        if ($countryName) {
            $countryName = trim($countryName);
            if ($countryName === 'تونس جزائر' || $countryName === 'تونس والجزائر') {
                return 'تونس و الجزائر';
            }
            if (in_array($countryName, ['تونس', 'الجزائر', 'تونس و الجزائر', 'مصر'])) {
                return $countryName;
            }
        }

        $map = [
            1 => 'تونس',
            2 => 'الجزائر',
            3 => 'تونس و الجزائر',
            4 => 'مصر',
        ];
        return $map[(int) $countryId] ?? 'تونس';
    }

    /**
     * Sanitize issue_date to prevent MySQL DST gap errors.
     */
    protected function sanitizeIssueDate(?string $dateStr): string
    {
        if (empty($dateStr)) {
            return now()->toDateTimeString();
        }

        $trimmed = trim($dateStr);

        // 1. If the hour is 00:, shift it to 01: to avoid DST gap errors on TIMESTAMP columns in MySQL
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+00:(\d{2}:\d{2})$/', $trimmed, $matches)) {
            return $matches[1] . ' 01:' . $matches[2];
        }

        // 2. If the hour is 02:xx:xx on a Sunday in March (DST start), shift it to 03:xx:xx
        if (preg_match('/^(\d{4}-03-\d{2})\s+02:(\d{2}:\d{2})$/', $trimmed, $matches)) {
            $datePart = $matches[1];
            $dayOfWeek = date('w', strtotime($datePart)); // 0 = Sunday
            if ($dayOfWeek == 0) {
                return $datePart . ' 03:' . $matches[2];
            }
        }

        return $trimmed;
    }
}
