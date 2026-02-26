<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;
use Carbon\Carbon;

class DailyPortionSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Cek Token Login (401 Unauthorized)
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        // 2. Cek Role (403 Forbidden) - Beri akses ke admin_dept atau head_dept
        if (!in_array($user->role, ['admin_dept', 'head_dept'])) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden Access: Invalid Role'], 403);
        }

        $adminDeptId = $user->department_id;

        $validOvertimes = OvertimeRequest::with('employee.shift')
            ->whereHas('employee', function ($query) use ($adminDeptId) {
                $query->where('department_id', $adminDeptId);
            })
            ->where('is_eligible_for_voucher', 1)
            ->whereIn('status', ['APPROVED', 'REDEEMED', 'SUBMITTED'])
            ->orderBy('date', 'desc')
            ->get();

        $summaryList = [];
        $todayTotalReq = 0;
        $todayDate = Carbon::today()->format('Y-m-d');

        foreach ($validOvertimes as $overtime) {
            $date = $overtime->date;
            
            $shiftName = null;
            if ($overtime->employee && $overtime->employee->shift) {
                $shiftName = $overtime->employee->shift->shift_name;
            }
            if (empty($shiftName)) {
                $shiftId = $overtime->employee->shift_id ?? 'Umum';
                $shiftName = 'Shift ' . $shiftId;
            }

            $groupKey = $date . '-' . $shiftName;

            if (!isset($summaryList[$groupKey])) {
                $status = ($date >= $todayDate) ? 'PENDING' : 'SENT';

                $summaryList[$groupKey] = [
                    'id' => $groupKey,
                    'tanggal' => Carbon::parse($date)->format('d M Y'),
                    'shift_name' => $shiftName,
                    'jml_karyawan' => 0,
                    'total_porsi' => 0,
                    'status' => $status
                ];
            }

            $summaryList[$groupKey]['jml_karyawan'] += 1;
            $summaryList[$groupKey]['total_porsi'] += 1;

            if ($date === $todayDate) {
                $todayTotalReq += 1;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'today_total_req' => $todayTotalReq,
                'histories' => array_values($summaryList)
            ]
        ]);
    }
}