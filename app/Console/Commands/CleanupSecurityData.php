<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\TwoFactorToken;
use Illuminate\Console\Command;

class CleanupSecurityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:cleanup
                            {--tokens : Clean expired 2FA tokens}
                            {--logs : Clean old activity logs}
                            {--all : Clean both tokens and logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired security data (2FA tokens, activity logs)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cleanTokens = $this->option('tokens') || $this->option('all');
        $cleanLogs = $this->option('logs') || $this->option('all');

        if (!$cleanTokens && !$cleanLogs) {
            $this->error('Please specify what to clean: --tokens, --logs, or --all');
            return Command::FAILURE;
        }

        $this->info('🧹 Starting security data cleanup...');

        if ($cleanTokens) {
            $this->cleanupExpiredTokens();
        }

        if ($cleanLogs) {
            $this->cleanupOldActivityLogs();
        }

        $this->info('✅ Security data cleanup completed!');
        return Command::SUCCESS;
    }

    /**
     * Clean up expired 2FA tokens
     */
    private function cleanupExpiredTokens(): void
    {
        $this->line('🔑 Cleaning expired 2FA tokens...');

        $deletedCount = TwoFactorToken::cleanExpired();

        if ($deletedCount > 0) {
            $this->info("   ✅ Deleted {$deletedCount} expired 2FA tokens");
        } else {
            $this->line('   ℹ️  No expired tokens to clean');
        }
    }

    /**
     * Clean up old activity logs
     */
    private function cleanupOldActivityLogs(): void
    {
        $this->line('📊 Cleaning old activity logs (older than 6 months)...');

        $deletedCount = ActivityLog::cleanOldLogs();

        if ($deletedCount > 0) {
            $this->info("   ✅ Deleted {$deletedCount} old activity log entries");
        } else {
            $this->line('   ℹ️  No old logs to clean');
        }
    }
}
