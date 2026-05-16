<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\ExcelImportController();

$reflection = new \ReflectionClass($controller);
$extractValueMethod = $reflection->getMethod('extractValue');
$extractValueMethod->setAccessible(true);

$rawData = [
    0 => 'TR-W0031',
    1 => 'شركة الطموح العربي',
    2 => '2025/08/29',
    3 => '9978-30',
    4 => '90.000',
    'رقم الوثيقة' => 'TR-W0031',
    'اسم المؤمن له' => 'شركة الطموح العربي',
    'تاريخ الاصدار' => '2025/08/29',
    'رقم اللوحة' => '9978-30',
    'القسط الصافي' => '90.000'
];

$keysToTest = ['اسم المؤمن له', 'مؤمن', 'الاسم', 'صاحب'];

$result = $extractValueMethod->invoke($controller, $rawData, $keysToTest);

echo "Extracted value: " . var_export($result, true) . "\n";
