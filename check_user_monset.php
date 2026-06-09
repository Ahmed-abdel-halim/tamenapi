<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$agent = \App\Models\BranchAgent::where('agent_name', 'like', '%احمد عبدالحليم%')
    ->orWhere('agency_name', 'like', '%احمد عبدالحليم%')
    ->first();
if ($agent) {
    echo "BRANCH AGENT FOUND BY NAME:\n";
    echo "ID: " . $agent->id . "\n";
    echo "Agency Name: " . $agent->agency_name . "\n";
    echo "Agent Name: " . $agent->agent_name . "\n";
    echo "User ID: " . ($agent->user_id ?? 'NULL') . "\n";
} else {
    echo "NO BRANCH AGENT FOUND BY NAME\n";
}
