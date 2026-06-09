<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$client = new Client([
    'verify'          => false,
    'timeout' => 15.0,
    'connect_timeout' => 5.0,
    'proxy'           => '',
    'curl'            => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
]);

try {
    $response = $client->request('POST', 'https://prodapi.lifo.ly/api/report/byoffice/2403', [
        'json' => [
            'user_name' => 'adminmli',
            'pass_word' => '20232024'
        ],
        'headers' => [
            'Accept' => 'application/json',
        ]
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    
    if (isset($data['code']) && $data['code'] === 1) {
        $reports = is_array($data['data']) ? $data['data'] : ($data['data']['data'] ?? []);
        
        echo "Found " . count($reports) . " reports for office 2403.\n";
        
        $users = [];
        foreach ($reports as $doc) {
            $uId = $doc['office_users_id'] ?? null;
            $uName = $doc['office_users']['username'] ?? 'unknown';
            if ($uId) {
                $users[$uId] = $uName;
            }
        }
        
        echo "Office Users in LIFO for office 2403:\n";
        print_r($users);
    } else {
        echo "LIFO API returned code: " . ($data['code'] ?? 'none') . "\n";
        echo "Message: " . ($data['message'] ?? 'none') . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
