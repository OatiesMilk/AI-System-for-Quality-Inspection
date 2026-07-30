<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit-logs:prune {--days=90 : Delete audit logs older than this many days}';

    protected $description = 'Delete audit log entries older than the given number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} audit log entr".($deleted === 1 ? 'y' : 'ies')." older than {$days} days.");

        return self::SUCCESS;
    }
}
