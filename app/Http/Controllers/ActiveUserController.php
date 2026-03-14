<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ActiveUserController extends Controller
{
    public function count(Request $request)
    {
        $minutes = $request->input('minute', AppConstants::USER_LAST_ACTIVITY_MINUTES);
        $threshold = Carbon::now()->subMinutes((int) $minutes);
        
        $activeCount = User::where('last_activity', '>=', $threshold)->count();

        return response()->json(['count' => $activeCount]);
    }

    public function peakRecords(Request $request)
    {
        $report = Report::where('name', 'User Report')->first();
        $records = [];

        if ($report) {
            $reportsData = $report->reports;

            if (is_string($reportsData)) {
                $reportsData = json_decode($reportsData, true) ?? [];
            } elseif (!is_array($reportsData)) {
                $reportsData = [];
            }

            if (isset($reportsData['users peak active list'])) {
                foreach ($reportsData['users peak active list'] as $timestamp => $data) {
                    $records[] = [
                        'timestamp' => $timestamp,
                        'count' => $data['count'] ?? 0,
                    ];
                }

                usort($records, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
            }
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $offset = ($page - 1) * $perPage;

        $items = array_slice($records, $offset, $perPage);

        return response()->json(new LengthAwarePaginator($items, count($records), $perPage, $page, ['path' => $request->url(), 'query' => $request->query()]));
    }
}