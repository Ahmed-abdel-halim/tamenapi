<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnionSyncService;

class SyncUnionDocuments extends Command
{
    protected $signature = 'lifo:sync-documents';
    protected $description = 'Synchronize international insurance documents from Union (LIFO) platform';

    public function handle(UnionSyncService $syncService)
    {
        $this->info('Starting Union (LIFO) document synchronization...');
        
        $stats = $syncService->sync();
        
        $this->info("Synchronization finished!");
        $this->line("Created: {$stats['created']}");
        $this->line("Updated: {$stats['updated']}");
        $this->line("Failed:  {$stats['failed']}");
        
        if (!empty($stats['errors'])) {
            $this->error('Errors encountered:');
            foreach ($stats['errors'] as $error) {
                $this->error("- " . $error);
            }
        }
    }
}
