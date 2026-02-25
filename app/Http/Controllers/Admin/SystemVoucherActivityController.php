<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OvertimeRequest;

class SystemVoucherActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'admin_system') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = OvertimeRequest::with(['employee.department'])
            ->where('is_eligible_for_voucher', 1);

        $stats = [
            'total' => (clone $query)->count(),
            'claimed' => (clone $query)->where('status', 'REDEEMED')->count(),
            'pending' => (clone $query)->whereIn('status', ['APPROVED', 'SUBMITTED'])->count(),
            'expired' => (clone $query)->where('status', 'EXPIRED')->count(),
        ];

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($empQ) use ($search) {
                    $empQ->where('full_name', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id') && $request->department_id !== 'ALL') {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('export') && $request->export == 'true') {
            $data = $query->orderBy('updated_at', 'desc')->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        }

        $data = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'data' => $data
        ]);
    }
}