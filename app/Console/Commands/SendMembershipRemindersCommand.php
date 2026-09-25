<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendMembershipRemindersCommand extends Command
{
    protected $signature = 'gms:send-reminders';

    protected $description = 'Process and dispatch GMS membership expiry, dues, and freeze ending notifications.';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Starting GMS Membership Reminders Processing...');

        $expiryCount = $notificationService->processExpiryReminders();
        $this->info("Generated {$expiryCount} expiry reminder notifications.");

        $duesCount = $notificationService->processDuesReminders();
        $this->info("Generated {$duesCount} dues reminder notifications.");

        $freezeCount = $notificationService->processFreezeReminders();
        $this->info("Generated {$freezeCount} freeze reminder notifications.");

        $cleanedCount = $notificationService->cleanUpReadNotifications(60);
        $this->info("Cleaned up {$cleanedCount} read notifications older than 60 days.");

        $this->info('GMS Membership Reminders Processing Complete.');

        return Command::SUCCESS;
    }
}
