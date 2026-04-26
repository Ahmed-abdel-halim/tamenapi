<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$hasColumn = Schema::hasColumn('users', 'is_blocked');
echo "Column 'is_blocked' exists: " . ($hasColumn ? 'YES' : 'NO') . "\n";

if ($hasColumn) {
    $blockedUsers = DB::table('users')->where('is_blocked', true)->count();
    echo "Blocked users count: " . $blockedUsers . "\n";
}
