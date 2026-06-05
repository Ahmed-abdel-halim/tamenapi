<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class LifoReportController extends Controller
{
    public function __construct()
    {
        // Increase memory and execution time limits to handle large LIFO datasets (e.g., 88k+ cards)
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
    }

    /**
     * Proxy request to the production LIFO API.
     */
    public function lifoProxy(Request $request, $any)
    {
        $targetUrl = 'https://prodapi.lifo.ly/' . $any;

        $client = new Client([
            'verify'          => false,
            'timeout'         => 30.0,
            'connect_timeout' => 10.0,
            'proxy'           => '',
            'curl'            => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
        ]);

        // Headers to strip before forwarding to LIFO
        $stripHeaders = [
            'host', 'content-length', 'cookie', 'origin', 'referer',
            'accept-encoding', 'sec-fetch-dest', 'sec-fetch-mode', 'sec-fetch-site',
            'connection', 'authorization', 'content-type',
        ];

        // Flatten Laravel's array-of-arrays headers into plain strings for Guzzle
        $flatHeaders = [];
        foreach ($request->headers->all() as $key => $value) {
            if (!in_array(strtolower($key), $stripHeaders)) {
                $flatHeaders[$key] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        $options = [
            'headers'     => $flatHeaders,
            'query'       => $request->query(),
            'http_errors' => false,
        ];

        // For non-GET: forward body to LIFO
        if (!$request->isMethod('get')) {
            $contentType = strtolower($request->headers->get('Content-Type', ''));
            
            if (str_contains($contentType, 'application/json')) {
                $rawBody = $request->getContent();
                if (!empty($rawBody)) {
                    $decoded = json_decode($rawBody, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                        $options['json'] = $decoded;
                    } else {
                        $options['body']                    = $rawBody;
                        $options['headers']['Content-Type'] = 'application/json';
                    }
                }
            } else {
                $allParams = $request->all();
                if (!empty($allParams)) {
                    $options['form_params'] = $allParams;
                }
            }
        }

        try {
            $response = $client->request($request->method(), $targetUrl, $options);
            return response($response->getBody()->getContents(), $response->getStatusCode())
                ->header('Content-Type', $response->getHeaderLine('Content-Type') ?: 'application/json');
        } catch (\Exception $e) {
            return response()->json([
                'code'    => 0,
                'success' => false,
                'message' => 'تعذر الاتصال بخادم الاتحاد: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Get paginated and filtered cards list.
     */
    public function cardsPaginated(Request $request)
    {
        $request->validate([
            'user_name'      => 'required|string',
            'pass_word'      => 'required|string',
            'category'       => 'required|string|in:all,active,cancel,sold',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
            'search'         => 'nullable|string',
            'office_name'    => 'nullable|string',
            'date_from'      => 'nullable|string',
            'date_to'        => 'nullable|string',
            'card_number'    => 'nullable|string',
            'request_number' => 'nullable|string',
        ]);

        $userName       = $request->user_name;
        $password       = $request->pass_word;
        $category       = $request->category;
        $page           = (int) $request->input('page', 1);
        $perPage        = (int) $request->input('per_page', 10);
        $search         = $request->input('search');
        $officeName     = $request->input('office_name');
        $dateFrom       = $request->input('date_from');
        $dateTo         = $request->input('date_to');
        $cardNumber     = $request->input('card_number');
        $reqNumberParam = $request->input('request_number');
        $forceRefresh   = $request->boolean('force_refresh', false);

        $userHash = md5($userName);
        $cacheKey = "lifo_cards_{$userHash}_{$category}";

        $list = null;
        $fromCache = true;

        if (!$forceRefresh) {
            $list = Cache::get($cacheKey);
        }

        if (!$list) {
            $fromCache = false;
            $endpoint = '';
            switch ($category) {
                case 'all':    $endpoint = '/cards/all';    break;
                case 'active': $endpoint = '/cards/active'; break;
                case 'cancel': $endpoint = '/cards/cancel'; break;
                case 'sold':   $endpoint = '/cards/sold';   break;
            }

            $targetUrl = 'https://prodapi.lifo.ly/api' . $endpoint;

            $client = new Client([
                'verify'          => false,
                'timeout'         => 180.0,
                'connect_timeout' => 15.0,
                'proxy'           => '',
                'curl'            => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
            ]);

            try {
                $response = $client->request('POST', $targetUrl, [
                    'form_params' => [
                        'user_name' => $userName,
                        'pass_word' => $password,
                    ]
                ]);

                $contents = $response->getBody()->getContents();
                $data = json_decode($contents, true);

                if (isset($data['code']) && $data['code'] === 1 && isset($data['data'])) {
                    $list = is_array($data['data']) ? $data['data'] : [];
                    Cache::put($cacheKey, $list, 86400); // 24 hours
                } else {
                    $msg = $data['message'] ?? $data['messages'] ?? 'فشل جلب البيانات من خادم الاتحاد';
                    return response()->json([
                        'success' => false,
                        'message' => $msg
                    ], 400);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر الاتصال بخادم الاتحاد: ' . $e->getMessage(),
                    'error'   => $e->getMessage()
                ], 500);
            }
        }

        // Filter list locally in a single pass
        $hasOffice = !empty($officeName);
        $hasCard = !empty($cardNumber);
        $hasReq = !empty($reqNumberParam);
        $hasDateFrom = !empty($dateFrom);
        $hasDateTo = !empty($dateTo);
        $hasSearch = !empty($search);

        if ($hasOffice) $officeName = strtolower(trim($officeName));
        if ($hasCard) $cardNumber = strtolower(trim($cardNumber));
        if ($hasReq) $reqNumberParam = strtolower(trim($reqNumberParam));
        if ($hasSearch) $search = strtolower(trim($search));

        if ($hasOffice || $hasCard || $hasReq || $hasDateFrom || $hasDateTo || $hasSearch) {
            $filteredList = [];
            foreach ($list as $card) {
                if ($hasOffice) {
                    $val = isset($card['offices']) ? strtolower($card['offices']) : '';
                    if (strpos($val, $officeName) === false) {
                        continue;
                    }
                }
                if ($hasCard) {
                    $num = isset($card['card_number']) ? strtolower($card['card_number']) : (isset($card['card_serial']) ? strtolower($card['card_serial']) : '');
                    if (strpos($num, $cardNumber) === false) {
                        continue;
                    }
                }
                if ($hasReq) {
                    $req = isset($card['request_numberr']) ? strtolower($card['request_numberr']) : '';
                    if (strpos($req, $reqNumberParam) === false) {
                        continue;
                    }
                }
                if ($hasDateFrom) {
                    $date = isset($card['created_at']) ? substr($card['created_at'], 0, 10) : '';
                    if ($date < $dateFrom) {
                        continue;
                    }
                }
                if ($hasDateTo) {
                    $date = isset($card['created_at']) ? substr($card['created_at'], 0, 10) : '';
                    if ($date > $dateTo) {
                        continue;
                    }
                }
                if ($hasSearch) {
                    $num = isset($card['card_number']) ? strtolower($card['card_number']) : (isset($card['card_serial']) ? strtolower($card['card_serial']) : '');
                    $req = isset($card['request_numberr']) ? strtolower($card['request_numberr']) : '';
                    $status = isset($card['cardstautesname']) ? strtolower($card['cardstautesname']) : '';
                    
                    if (strpos($num, $search) === false && 
                        strpos($req, $search) === false && 
                        strpos($status, $search) === false) {
                        continue;
                    }
                }
                $filteredList[] = $card;
            }
        } else {
            $filteredList = $list;
        }

        $total = count($filteredList);
        $offset = ($page - 1) * $perPage;
        $slicedList = array_slice($filteredList, $offset, $perPage);

        return response()->json([
            'success'    => true,
            'data'       => $slicedList,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'from_cache' => $fromCache,
        ]);
    }

    /**
     * Get paginated and filtered reports.
     */
    public function reportsPaginated(Request $request)
    {
        $request->validate([
            'user_name'             => 'required|string',
            'pass_word'             => 'required|string',
            'date_from'             => 'nullable|string',
            'date_to'               => 'nullable|string',
            'search_office_id'      => 'nullable|string',
            'search_office_user_id' => 'nullable|string',
            'customer_name'         => 'nullable|string',
            'card_number'           => 'nullable|string',
            'plate_number'          => 'nullable|string',
            'chassis_number'        => 'nullable|string',
            'page'                  => 'nullable|integer|min:1',
            'per_page'              => 'nullable|integer|min:1|max:500',
            'force_refresh'         => 'nullable|boolean',
        ]);

        $userName            = $request->user_name;
        $password            = $request->pass_word;
        $dateFrom            = $request->date_from;
        $dateTo              = $request->date_to;
        $searchOfficeId      = $request->search_office_id;
        $searchOfficeUserId  = $request->search_office_user_id;
        $customerName        = $request->customer_name;
        $cardNumber          = $request->card_number;
        $plateNumber         = $request->plate_number;
        $chassisNumber       = $request->chassis_number;
        $page                = (int) $request->input('page', 1);
        $perPage             = (int) $request->input('per_page', 10);
        $forceRefresh        = $request->boolean('force_refresh', false);

        $userHash = md5($userName);
        $fromCache = true;

        // 1. Get offices list (cached)
        $officesCacheKey = "lifo_offices_{$userHash}";
        $officesList = Cache::get($officesCacheKey);
        if (!$officesList) {
            try {
                $response = Http::timeout(30)->withoutVerifying()->post('https://prodapi.lifo.ly/api/offices/all', [
                    'user_name' => $userName,
                    'pass_word' => $password,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        $officesList = $data['data'];
                        Cache::put($officesCacheKey, $officesList, 7200);
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        $officesList = $officesList ?? [];

        // 2. Get cards list (cached)
        $cardsCacheKey = "lifo_cards_{$userHash}_all";
        $cardsList = Cache::get($cardsCacheKey);
        if (!$cardsList) {
            try {
                $response = Http::timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/cards/all', [
                    'user_name' => $userName,
                    'pass_word' => $password,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        $cardsList = $data['data'];
                        Cache::put($cardsCacheKey, $cardsList, 7200);
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        $cardsList = $cardsList ?? [];

        // Build cards map for fast mapping
        $cardsMap = [];
        foreach ($cardsList as $card) {
            if (!empty($card['id'])) {
                $cardsMap[$card['id']] = $card['card_number'] ?? $card['card_serial'] ?? '-';
            }
        }

        // 3. Fetch reports
        $reportsCacheKey = "lifo_reports_data_{$userHash}";
        $reports = null;

        if (!$forceRefresh) {
            $reports = Cache::get($reportsCacheKey);
        }

        if (!$reports) {
            $fromCache = false;
            $fetchedReports = [];

            if (!empty($searchOfficeId)) {
                try {
                    $response = Http::timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/report/byoffice/' . $searchOfficeId, [
                        'user_name' => $userName,
                        'pass_word' => $password,
                    ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['code']) && $data['code'] === 1) {
                            $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                            $officeObj = null;
                            foreach ($officesList as $o) {
                                if ((string)$o['id'] === (string)$searchOfficeId) {
                                    $officeObj = $o;
                                    break;
                                }
                            }
                            foreach ($list as $doc) {
                                $doc['offices_id'] = $searchOfficeId;
                                $doc['offices'] = $doc['offices'] ?? [
                                    'id' => (int)$searchOfficeId,
                                    'name' => $officeObj ? $officeObj['name'] : "مكتب {$searchOfficeId}"
                                ];
                                $fetchedReports[] = $doc;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ أثناء جلب تقارير المكتب: ' . $e->getMessage()
                    ], 500);
                }
            } else {
                // Find active offices names from cards list
                $activeOfficeNames = [];
                foreach ($cardsList as $card) {
                    if (!empty($card['offices']) && ($card['cardstautesname'] === 'البطاقات المباعة' || $card['cardstautesname'] === 'البطاقات المصدرة')) {
                        $officeClean = trim($card['offices']);
                        if ($officeClean !== 'لدي الشركة') {
                            $activeOfficeNames[$officeClean] = true;
                        }
                    }
                }

                $activeOffices = [];
                foreach ($officesList as $office) {
                    $officeNameClean = trim($office['name'] ?? '');
                    if (isset($activeOfficeNames[$officeNameClean])) {
                        $activeOffices[] = $office;
                    }
                }

                // Fetch concurrently
                try {
                    $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($userName, $password, $activeOffices) {
                        $requests = [];
                        $requests[] = $pool->as('company')->timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/report/all', [
                            'user_name' => $userName,
                            'pass_word' => $password,
                        ]);

                        foreach ($activeOffices as $office) {
                            $requests[] = $pool->as('office_' . $office['id'])->timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/report/byoffice/' . $office['id'], [
                                'user_name' => $userName,
                                'pass_word' => $password,
                            ]);
                        }
                        return $requests;
                    });

                    // Parse company response
                    if (isset($responses['company']) && $responses['company']->successful()) {
                        $data = $responses['company']->json();
                        if (isset($data['code']) && $data['code'] === 1) {
                            $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                            foreach ($list as $doc) {
                                $doc['offices_id'] = null;
                                $doc['offices'] = null;
                                $fetchedReports[] = $doc;
                            }
                        }
                    }

                    // Parse offices responses
                    foreach ($activeOffices as $office) {
                        $key = 'office_' . $office['id'];
                        if (isset($responses[$key]) && $responses[$key]->successful()) {
                            $data = $responses[$key]->json();
                            if (isset($data['code']) && $data['code'] === 1) {
                                $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                                foreach ($list as $doc) {
                                    $doc['offices_id'] = $office['id'];
                                    $doc['offices'] = $doc['offices'] ?? ['id' => $office['id'], 'name' => $office['name']];
                                    $fetchedReports[] = $doc;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'تعذر الاتصال بخادم الاتحاد لجلب التقارير: ' . $e->getMessage()
                    ], 500);
                }
            }

            $reports = $fetchedReports;
            Cache::put($reportsCacheKey, $reports, 7200); // 2 hours
        }

        // 4. Filter reports based on search inputs
        $filteredReports = [];
        foreach ($reports as $doc) {
            if (!empty($searchOfficeId)) {
                if ((string)($doc['offices_id'] ?? '') !== (string)$searchOfficeId) {
                    continue;
                }
            }

            if (!empty($searchOfficeUserId)) {
                $docOfficeUser = $doc['office_users_id'] ?? '';
                if (strpos((string)$docOfficeUser, trim($searchOfficeUserId)) === false) {
                    continue;
                }
            }

            if (!empty($customerName)) {
                $docCustName = strtolower($doc['insurance_name'] ?? '');
                if (strpos($docCustName, strtolower(trim($customerName))) === false) {
                    continue;
                }
            }

            $resolvedCardNumber = $cardsMap[$doc['cards_id'] ?? ''] ?? $doc['policyNumber'] ?? $doc['card_number'] ?? '-';
            if (!empty($cardNumber)) {
                if (strpos(strtolower($resolvedCardNumber), strtolower(trim($cardNumber))) === false) {
                    continue;
                }
            }

            if (!empty($plateNumber)) {
                $docPlate = strtolower($doc['plate_number'] ?? '');
                if (strpos($docPlate, strtolower(trim($plateNumber))) === false) {
                    continue;
                }
            }

            if (!empty($chassisNumber)) {
                $docChassis = strtolower($doc['chassis_number'] ?? '');
                if (strpos($docChassis, strtolower(trim($chassisNumber))) === false) {
                    continue;
                }
            }

            if (!empty($dateFrom) || !empty($dateTo)) {
                if (empty($doc['issuing_date'])) {
                    continue;
                }
                $datePart = substr($doc['issuing_date'], 0, 10);
                if (!empty($dateFrom) && $datePart < $dateFrom) {
                    continue;
                }
                if (!empty($dateTo) && $datePart > $dateTo) {
                    continue;
                }
            }

            $doc['resolved_card_number'] = $resolvedCardNumber;
            $filteredReports[] = $doc;
        }

        // 5. Calculate totals
        $totals = [
            'installment' => 0.0,
            'tax'         => 0.0,
            'stamp'       => 0.0,
            'supervision' => 0.0,
            'version'     => 0.0,
            'total'       => 0.0,
        ];
        foreach ($filteredReports as $doc) {
            $totals['installment'] += (float)($doc['insurance_installment'] ?? 0);
            $totals['tax']         += (float)($doc['insurance_tax'] ?? 0);
            $totals['stamp']       += (float)($doc['insurance_stamp'] ?? 0);
            $totals['supervision'] += (float)($doc['insurance_supervision'] ?? 0);
            $totals['version']     += (float)($doc['insurance_version'] ?? 0);
            $totals['total']       += (float)($doc['insurance_total'] ?? 0);
        }

        $formattedTotals = [
            'installment' => number_format($totals['installment'], 3, '.', ''),
            'tax'         => number_format($totals['tax'], 3, '.', ''),
            'stamp'       => number_format($totals['stamp'], 3, '.', ''),
            'supervision' => number_format($totals['supervision'], 3, '.', ''),
            'version'     => number_format($totals['version'], 3, '.', ''),
            'total'       => number_format($totals['total'], 3, '.', ''),
        ];

        $total = count($filteredReports);
        $offset = ($page - 1) * $perPage;
        $slicedReports = array_slice($filteredReports, $offset, $perPage);

        return response()->json([
            'success'    => true,
            'data'       => $slicedReports,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'totals'     => $formattedTotals,
            'from_cache' => $fromCache,
        ]);
    }

    /**
     * Get inventory summary counts.
     */
    public function inventorySummary(Request $request)
    {
        $request->validate([
            'user_name'     => 'required|string',
            'pass_word'     => 'required|string',
            'force_refresh' => 'nullable|boolean',
        ]);

        $userName     = $request->user_name;
        $password     = $request->pass_word;
        $forceRefresh = $request->boolean('force_refresh', false);

        $userHash = md5($userName);
        $cardsCacheKey = "lifo_cards_{$userHash}_all";
        $cardsList = null;

        if (!$forceRefresh) {
            $cardsList = Cache::get($cardsCacheKey);
        }

        if (!$cardsList) {
            try {
                $response = Http::timeout(180)->withoutVerifying()->post('https://prodapi.lifo.ly/api/cards/all', [
                    'user_name' => $userName,
                    'pass_word' => $password,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        $cardsList = $data['data'];
                        Cache::put($cardsCacheKey, $cardsList, 7200);
                    } else {
                        $msg = $data['message'] ?? $data['messages'] ?? 'فشل جلب البيانات من خادم الاتحاد';
                        return response()->json(['success' => false, 'message' => $msg], 400);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'فشل جلب البيانات من خادم الاتحاد'], 400);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر الاتصال بخادم الاتحاد: ' . $e->getMessage()
                ], 500);
            }
        }

        $companyStock = ['active' => 0, 'sold' => 0, 'canceled' => 0];
        $officesStock = [];

        foreach ($cardsList as $card) {
            $office = isset($card['offices']) ? trim($card['offices']) : '';
            $status = $card['cardstautesname'] ?? '';

            $isCompany = ($office === 'لدي الشركة' || empty($office));

            if ($isCompany) {
                if ($status === 'البطاقات المعينة' || $status === 'البطاقات النشطة') {
                    $companyStock['active']++;
                } else if ($status === 'البطاقات المباعة' || $status === 'البطاقات المصدرة') {
                    $companyStock['sold']++;
                } else if ($status === 'البطاقات الملغية') {
                    $companyStock['canceled']++;
                }
            } else {
                if (!isset($officesStock[$office])) {
                    $officesStock[$office] = [
                        'office'   => $office,
                        'active'   => 0,
                        'sold'     => 0,
                        'canceled' => 0,
                    ];
                }
                if ($status === 'البطاقات المعينة' || $status === 'البطاقات النشطة') {
                    $officesStock[$office]['active']++;
                } else if ($status === 'البطاقات المباعة' || $status === 'البطاقات المصدرة') {
                    $officesStock[$office]['sold']++;
                } else if ($status === 'البطاقات الملغية') {
                    $officesStock[$office]['canceled']++;
                }
            }
        }

        return response()->json([
            'success'       => true,
            'company_stock' => $companyStock,
            'offices_stock' => array_values($officesStock),
        ]);
    }

    /**
     * Get aggregated financials & counts per office.
     */
    public function officesAggregated(Request $request)
    {
        $request->validate([
            'user_name'     => 'required|string',
            'pass_word'     => 'required|string',
            'date_from'     => 'nullable|string',
            'date_to'       => 'nullable|string',
            'force_refresh' => 'nullable|boolean',
        ]);

        $userName     = $request->user_name;
        $password     = $request->pass_word;
        $dateFrom     = $request->date_from;
        $dateTo       = $request->date_to;
        $forceRefresh = $request->boolean('force_refresh', false);

        $userHash = md5($userName);
        $fromCache = true;

        // 1. Get offices list (cached)
        $officesCacheKey = "lifo_offices_{$userHash}";
        $officesList = Cache::get($officesCacheKey);
        if (!$officesList) {
            try {
                $response = Http::timeout(30)->withoutVerifying()->post('https://prodapi.lifo.ly/api/offices/all', [
                    'user_name' => $userName,
                    'pass_word' => $password,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        $officesList = $data['data'];
                        Cache::put($officesCacheKey, $officesList, 7200);
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        $officesList = $officesList ?? [];

        // 2. Get cards list (cached)
        $cardsCacheKey = "lifo_cards_{$userHash}_all";
        $cardsList = Cache::get($cardsCacheKey);
        if (!$cardsList) {
            try {
                $response = Http::timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/cards/all', [
                    'user_name' => $userName,
                    'pass_word' => $password,
                ]);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                        $cardsList = $data['data'];
                        Cache::put($cardsCacheKey, $cardsList, 7200);
                    }
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }
        $cardsList = $cardsList ?? [];

        // 3. Fetch reports (with cache)
        $reportsCacheKey = "lifo_reports_data_{$userHash}";
        $reports = null;

        if (!$forceRefresh) {
            $reports = Cache::get($reportsCacheKey);
        }

        if (!$reports) {
            $fromCache = false;
            $fetchedReports = [];

            // Find active offices names from cards list
            $activeOfficeNames = [];
            foreach ($cardsList as $card) {
                if (!empty($card['offices']) && ($card['cardstautesname'] === 'البطاقات المباعة' || $card['cardstautesname'] === 'البطاقات المصدرة')) {
                    $officeClean = trim($card['offices']);
                    if ($officeClean !== 'لدي الشركة') {
                        $activeOfficeNames[$officeClean] = true;
                    }
                }
            }

            $activeOffices = [];
            foreach ($officesList as $office) {
                $officeNameClean = trim($office['name'] ?? '');
                if (isset($activeOfficeNames[$officeNameClean])) {
                    $activeOffices[] = $office;
                }
            }

            // Fetch concurrently
            try {
                $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($userName, $password, $activeOffices) {
                    $requests = [];
                    $requests[] = $pool->as('company')->timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/report/all', [
                        'user_name' => $userName,
                        'pass_word' => $password,
                    ]);

                    foreach ($activeOffices as $office) {
                        $requests[] = $pool->as('office_' . $office['id'])->timeout(45)->withoutVerifying()->post('https://prodapi.lifo.ly/api/report/byoffice/' . $office['id'], [
                            'user_name' => $userName,
                            'pass_word' => $password,
                        ]);
                    }
                    return $requests;
                });

                // Parse company response
                if (isset($responses['company']) && $responses['company']->successful()) {
                    $data = $responses['company']->json();
                    if (isset($data['code']) && $data['code'] === 1) {
                        $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                        foreach ($list as $doc) {
                            $doc['offices_id'] = null;
                            $doc['offices'] = null;
                            $fetchedReports[] = $doc;
                        }
                    }
                }

                // Parse offices responses
                foreach ($activeOffices as $office) {
                    $key = 'office_' . $office['id'];
                    if (isset($responses[$key]) && $responses[$key]->successful()) {
                        $data = $responses[$key]->json();
                        if (isset($data['code']) && $data['code'] === 1) {
                            $list = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
                            foreach ($list as $doc) {
                                $doc['offices_id'] = $office['id'];
                                $doc['offices'] = $doc['offices'] ?? ['id' => $office['id'], 'name' => $office['name']];
                                $fetchedReports[] = $doc;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر الاتصال بخادم الاتحاد لجلب التقارير: ' . $e->getMessage()
                ], 500);
            }

            $reports = $fetchedReports;
            Cache::put($reportsCacheKey, $reports, 7200); // 2 hours
        }

        // 4. Map offices names for quick resolution
        $officeNamesMap = [];
        foreach ($officesList as $office) {
            $officeNamesMap[$office['id']] = trim($office['name']);
        }

        // 5. Canceled Cards Grouping
        $officeCanceledCounts = [];
        foreach ($cardsList as $card) {
            if (!empty($card['offices']) && ($card['cardstautesname'] === 'البطاقات الملغية' || $card['cardstautesname'] === 'الملغية')) {
                $officeClean = trim($card['offices']);
                $officeKey = ($officeClean === 'لدي الشركة' || empty($officeClean)) ? 'الفرع الرئيسي' : $officeClean;

                if (!empty($dateFrom) || !empty($dateTo)) {
                    $cardDate = substr($card['created_at'] ?? '', 0, 10);
                    if (!empty($dateFrom) && $cardDate < $dateFrom) continue;
                    if (!empty($dateTo) && $cardDate > $dateTo) continue;
                }

                $officeCanceledCounts[$officeKey] = ($officeCanceledCounts[$officeKey] ?? 0) + 1;
            }
        }

        // 6. Reports Aggregation & Grouping
        $map = [];
        foreach ($reports as $doc) {
            if (!empty($dateFrom) || !empty($dateTo)) {
                if (empty($doc['issuing_date'])) continue;
                $datePart = substr($doc['issuing_date'], 0, 10);
                if (!empty($dateFrom) && $datePart < $dateFrom) continue;
                if (!empty($dateTo) && $datePart > $dateTo) continue;
            }

            $officeId = $doc['offices_id'] ?? null;
            $officeName = 'الفرع الرئيسي';
            if ($officeId !== null && isset($officeNamesMap[$officeId])) {
                $officeName = $officeNamesMap[$officeId];
            } else if (!empty($doc['offices']['name'])) {
                $officeName = trim($doc['offices']['name']);
            }

            if (!isset($map[$officeName])) {
                $map[$officeName] = [
                    'officeName'    => $officeName,
                    'soldCount'     => 0,
                    'canceledCount' => $officeCanceledCounts[$officeName] ?? 0,
                    'installment'   => 0.0,
                    'tax'           => 0.0,
                    'stamp'         => 0.0,
                    'supervision'   => 0.0,
                    'version'       => 0.0,
                    'total'         => 0.0,
                ];
            }

            $map[$officeName]['soldCount']++;
            $map[$officeName]['installment'] += (float)($doc['insurance_installment'] ?? 0);
            $map[$officeName]['tax']         += (float)($doc['insurance_tax'] ?? 0);
            $map[$officeName]['stamp']       += (float)($doc['insurance_stamp'] ?? 0);
            $map[$officeName]['supervision'] += (float)($doc['insurance_supervision'] ?? 0);
            $map[$officeName]['version']     += (float)($doc['insurance_version'] ?? 0);
            $map[$officeName]['total']       += (float)($doc['insurance_total'] ?? 0);
        }

        // Include offices that had cancellations but no sales in the range
        foreach ($officeCanceledCounts as $officeName => $count) {
            if (!isset($map[$officeName])) {
                $map[$officeName] = [
                    'officeName'    => $officeName,
                    'soldCount'     => 0,
                    'canceledCount' => $count,
                    'installment'   => 0.0,
                    'tax'           => 0.0,
                    'stamp'         => 0.0,
                    'supervision'   => 0.0,
                    'version'       => 0.0,
                    'total'         => 0.0,
                ];
            }
        }

        // Format all values for JSON consistency
        $result = [];
        foreach ($map as $key => $val) {
            $result[] = [
                'officeName'    => $val['officeName'],
                'soldCount'     => $val['soldCount'],
                'canceledCount' => $val['canceledCount'],
                'installment'   => (float)number_format($val['installment'], 3, '.', ''),
                'tax'           => (float)number_format($val['tax'], 3, '.', ''),
                'stamp'         => (float)number_format($val['stamp'], 3, '.', ''),
                'supervision'   => (float)number_format($val['supervision'], 3, '.', ''),
                'version'       => (float)number_format($val['version'], 3, '.', ''),
                'total'         => (float)number_format($val['total'], 3, '.', ''),
            ];
        }

        return response()->json([
            'success'    => true,
            'data'       => $result,
            'from_cache' => $fromCache,
        ]);
    }

    /**
     * Diagnostic endpoint to test connection to LIFO.
     */
    public function testLifoConnection()
    {
        $results = [];
        
        // Test 1: Resolve DNS
        $ip = gethostbyname('prodapi.lifo.ly');
        $results['dns_resolution'] = [
            'host' => 'prodapi.lifo.ly',
            'ip' => $ip,
            'success' => ($ip !== 'prodapi.lifo.ly')
        ];
        
        // Test 2: Curl test
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://prodapi.lifo.ly/api/countries/all");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4 in curl test
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $output = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        $results['curl_test'] = [
            'url' => 'https://prodapi.lifo.ly/api/countries/all',
            'http_code' => $info['http_code'],
            'error' => $error,
            'total_time' => $info['total_time'],
            'output_snippet' => substr($output, 0, 500)
        ];
        
        // Test 3: Laravel Http client test
        try {
            $res = Http::timeout(10)
                ->withoutVerifying()
                ->withOptions([
                    'force_ip_resolve' => 'v4',
                    'proxy' => ''
                ])
                ->get('https://prodapi.lifo.ly/api/countries/all');
            $results['laravel_http_test'] = [
                'status' => $res->status(),
                'body' => substr($res->body(), 0, 500)
            ];
        } catch (\Exception $e) {
            $results['laravel_http_test'] = [
                'error' => $e->getMessage()
            ];
        }
        
        return response()->json($results);
    }
}
