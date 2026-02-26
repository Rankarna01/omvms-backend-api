<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;

class SystemApprovalOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin_system') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = OvertimeRequest::with(['employee.department']);

        $stats = [
            'step_1_waiting' => (clone $query)->whereIn('status', ['SUBMITTED', 'PENDING', 'WAITING_DEPT'])->count(),
            'step_2_waiting' => (clone $query)->where('status', 'WAITING_HEAD')->count(),
            'completed' => (clone $query)->whereIn('status', ['APPROVED', 'REDEEMED'])->count(),
            'terminated' => (clone $query)->where('status', 'REJECTED')->count(),
        ];

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('full_name', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            if ($request->status === 'WAITING_DEPT') {
                $query->whereIn('status', ['SUBMITTED', 'PENDING', 'WAITING_DEPT']);
            } elseif ($request->status === 'COMPLETED') {
                $query->whereIn('status', ['APPROVED', 'REDEEMED']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $data = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'data' => $data
        ]);
    }
}