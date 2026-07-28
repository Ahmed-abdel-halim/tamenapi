<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAgentPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reset-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zero out all received payments for all branch agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting reset of all agent received payments...');

        // 1. Reset paid_amount in monthly_account_closures
        $closuresUpdated = DB::table('monthly_account_closures')->update(['paid_amount' => 0]);
        $this->info("Reset paid_amount to 0 for {$closuresUpdated} monthly closures.");

        // 2. Clear payment_vouchers table if exists
        if (DB::getSchemaBuilder()->hasTable('payment_vouchers')) {
            DB::table('payment_vouchers')->truncate();
            $this->info('Cleared all payment vouchers.');
        }

        $this->info('Successfully zeroed out all received amounts for all agents!');
        return Command::SUCCESS;
    }
}
