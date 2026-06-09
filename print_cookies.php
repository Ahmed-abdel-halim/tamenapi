<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

$cookieJar = new CookieJar();
$client = new Client([
    'verify'          => false,
    'timeout' => 10.0,
    'cookies'         => $cookieJar,
    'curl'            => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
]);

$response = $client->request('GET', 'https://prod.lifo.ly/office/login');
echo "Headers:\n";
print_r($response->getHeaders());
echo "\nCookies:\n";
print_r($cookieJar->toArray());
