<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnionSyncService;
use Illuminate\Support\Facades\Cache;

class SyncUnionDocuments extends Command
{
    protected $signature = 'lifo:sync-documents';
    protected $description = 'Synchronize international insurance documents from Union (LIFO) platform';

    public function handle(UnionSyncService $syncService)
    {
        // Mark as running in Cache so frontend can track
        Cache::put('union_sync_status', [
            'status'  => 'running',
            'message' => 'جاري المزامنة مع الاتحاد...',
            'started_at' => now()->toDateTimeString(),
        ], 1800);

        $this->info('Starting Union (LIFO) document synchronization...');

        try {
            $stats = $syncService->sync();

            // Save result to Cache
            Cache::put('union_sync_status', [
                'status'  => 'completed',
                'message' => 'تمت المزامنة بنجاح',
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'failed'  => $stats['failed'],
                'errors'  => array_slice($stats['errors'] ?? [], 0, 10),
                'completed_at' => now()->toDateTimeString(),
            ], 1800);

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
        } catch (\Exception $e) {
            Cache::put('union_sync_status', [
                'status'  => 'failed',
                'message' => 'فشلت المزامنة: ' . $e->getMessage(),
                'completed_at' => now()->toDateTimeString(),
            ], 1800);

            $this->error('Sync failed: ' . $e->getMessage());
        }
    }
}
