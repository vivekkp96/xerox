<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeleteOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications created more than a month ago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        $oneWeekAgo = Carbon::now()->subWeek();

        // Delete notifications where user_read_flag = 0 and older than 3 months
        $countUnread = Notification::where('user_read_flag', 0)
            ->where('created_at', '<', $threeMonthsAgo)
            ->delete();

        // Delete notifications where user_read_flag = 1 and older than 1 week
        $countRead = Notification::where('user_read_flag', 1)
            ->where('created_at', '<', $oneWeekAgo)
            ->delete();

        Log::info("Deleted {$countUnread} unread and {$countRead} read notifications.");
    }
}
