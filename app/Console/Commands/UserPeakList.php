<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Report;
use Carbon\Carbon;
use App\Constants\AppConstants;

class UserPeakList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-peak-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the count of active users from the last n minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Calculate the time 30 minutes ago
        $timeLimit = Carbon::now()->subMinutes(AppConstants::CALCULATE_USER_PEAK_LIST_MINUTES);

        $activeCount = User::where('last_activity', '>=', $timeLimit)->count();

        $this->info("Active users (last " . AppConstants::CALCULATE_USER_PEAK_LIST_MINUTES . " mins): {$activeCount}");

        if ($activeCount > AppConstants::CALCULATE_USER_PEAK_LIST_ACTIVE_COUNT) {
            $report = Report::where('name', 'User Report')->first();

            if ($report) {
                $reportsData = $report->reports;

                // Decode if it's a JSON string, otherwise assume it's already an array (model casting)
                if (is_string($reportsData)) {
                    $reportsData = json_decode($reportsData, true) ?? [];
                } elseif (!is_array($reportsData)) {
                    $reportsData = [];
                }

                $reportsData['users peak active list'][Carbon::now()->toDateTimeString()] = [
                    'count' => $activeCount
                ];

                // Sort by timestamp and keep only the latest 100 records
                if (count($reportsData['users peak active list']) > 2) {
                    ksort($reportsData['users peak active list']);
                    $reportsData['users peak active list'] = array_slice($reportsData['users peak active list'], -100, null, true);
                }

                $report->reports = $reportsData;
                $report->save();
            }
        }
    }
}
