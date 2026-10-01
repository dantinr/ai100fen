<?php

namespace App\Console\Commands;

use App\Support\CommitHistory;
use Illuminate\Console\Command;
use Throwable;

class SyncCommitHistory extends Command
{
    protected $signature = 'project:sync-history';

    protected $description = 'Generate a private snapshot of this checkout’s commit history';

    public function handle(CommitHistory $history): int
    {
        try {
            $count = $history->sync();
            $this->info("Synced {$count} project commits.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Commit history sync failed. Check Git availability and storage permissions; the previous snapshot is retained.');

            return self::FAILURE;
        }
    }
}
