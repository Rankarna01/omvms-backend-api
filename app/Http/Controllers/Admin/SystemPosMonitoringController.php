<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class SystemPosMonitoringController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user || $user->role !== 'admin_system') {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }

            $selectedDate = $request->filled('date') ? Carbon::parse($request->date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

            $baseQuery = OvertimeRequest::where('status', 'REDEEMED');

            $totalAllTime = (clone $baseQuery)->count();
            
            $todayRedeemed = (clone $baseQuery)->whereDate('updated_at', $selectedDate)
                                               ->with(['employee.department'])
                                               ->get();
            $totalToday = $todayRedeemed->count();

            $deptCounts = [];
            foreach ($todayRedeemed as $item) {
                $deptName = $item->employee?->department?->dept_name ?? 'General';
                
                if (!isset($deptCounts[$deptName])) {
                    $deptCounts[$deptName] = 0;
                }
                $deptCounts[$deptName]++;
            }

            $departmentStats = [];
            foreach ($deptCounts as $name => $count) {
                $percentage = $totalToday > 0 ? round(($count / $totalToday) * 100) : 0;
                $departmentStats[] = [
                    'dept_name' => $name,
                    'count' => $count,
                    'percentage' => $percentage
                ];
            }

            usort($departmentStats, function($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            $liveTransactionsRaw = (clone $baseQuery)
                ->whereDate('updated_at', $selectedDate)
                ->with(['employee.department'])
                ->orderBy('updated_at', 'desc')
                ->take(15)
                ->get();

            $liveTransactions = $liveTransactionsRaw->map(function ($item) {
                $time = $item->updated_at ? Carbon::parse($item->updated_at)->format('H:i') : '00:00';
                $location = $item->canteen_name ?? 'Kantin Pusat';

                return [
                    'id' => $item->id,
                    'time' => $time,
                    'location' => $location,
                    'employee_name' => $item->employee?->full_name ?? 'Unknown Employee',
                    'dept_name' => $item->employee?->department?->dept_name ?? 'General',
                    'voucher_code' => 'VCH-OMVMS-' . str_pad($item->id, 4, '0', STR_PAD_LEFT)
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_today' => $totalToday,
                        'total_all_time' => $totalAllTime,
                        'date' => $selectedDate
                    ],
                    'department_stats' => $departmentStats,
                    'live_transactions' => $liveTransactions
                ]
            ]);

        } catch (Exception $e) {
            Log::error('POS Monitoring Error: ' . $e->getMessage() . ' on line ' . $e->getLine());

            return response()->json([
                'status' => 'error',
                'message' => 'Backend Error: ' . $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}