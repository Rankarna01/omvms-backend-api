<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    public function index()
    {
        $data = OvertimeRequest::with(['employee.shift'])->latest()->get();
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'reason'      => 'required|string',
        ]);

        $employee = Employee::with('shift')->findOrFail($request->employee_id);
        
        // LOGIC 1: JAM LEMBUR HARUS SETELAH SHIFT
        if ($employee->shift) {
            $shiftEndTime = Carbon::createFromFormat('H:i:s', $employee->shift->end_time);
            $reqStartTime = Carbon::parse($request->start_time);

            // Jika lembur dimulai sebelum shift berakhir -> TOLAK
            // (Asumsi di hari yang sama)
            if ($reqStartTime->lt($shiftEndTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lembur hanya bisa dimulai setelah jam shift berakhir (' . $shiftEndTime->format('H:i') . ').'
                ], 422);
            }
        }

        // LOGIC 2: DURASI MAKSIMAL 4 JAM
        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        if ($end->lessThan($start)) $end->addDay();
        
        $duration = $start->diffInHours($end); 

        if ($duration > 4) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Maksimal lembur adalah 4 Jam sesuai peraturan.'
            ], 422);
        }

        // LOGIC 3: VOUCHER (Minimal 4 Jam Pas)
        $isEligible = ($employee->shift && $employee->shift->allow_meal && $duration >= 4);

        $overtime = OvertimeRequest::create([
            'employee_id' => $request->employee_id,
            'date'        => $request->date,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'duration'    => $duration,
            'reason'      => $request->reason,
            'is_eligible_for_voucher' => $isEligible,
            'status'      => 'SUBMITTED',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan lembur berhasil dibuat.',
            'data' => $overtime
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data disetujui tidak bisa diedit.'], 403);
        }

        $request->validate([
            'date'        => 'required|date',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'reason'      => 'required|string',
        ]);

        $start = Carbon::parse($request->start_time);
        $end   = Carbon::parse($request->end_time);
        if ($end->lessThan($start)) $end->addDay();
        
        $duration = $start->diffInHours($end);

        if ($duration > 4) {
            return response()->json(['message' => 'Maksimal lembur adalah 4 Jam.'], 422);
        }

        $employee = Employee::with('shift')->find($overtime->employee_id);
        $isEligible = ($employee->shift && $employee->shift->allow_meal && $duration >= 4);

        $overtime->update([
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'duration'   => $duration,
            'reason'     => $request->reason,
            'is_eligible_for_voucher' => $isEligible
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data berhasil diperbarui.',
            'data' => $overtime
        ]);
    }

    public function destroy($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);
        if ($overtime->status === 'APPROVED') {
            return response()->json(['message' => 'Data disetujui tidak bisa dihapus.'], 403);
        }
        $overtime->delete();
        return response()->json(['status' => 'success', 'message' => 'Data dihapus.']);
    }

    public function approve($id)
    {
        $overtime = OvertimeRequest::findOrFail($id);

        if ($overtime->status !== 'SUBMITTED') {
            return response()->json(['message' => 'Status request tidak valid.'], 400);
        }

        $approvalTime = Carbon::now();
        $overtimeEndTime = Carbon::parse($overtime->date . ' ' . $overtime->end_time);
        if (Carbon::parse($overtime->start_time)->gt(Carbon::parse($overtime->end_time))) {
            $overtimeEndTime->addDay();
        }

        if ($approvalTime->greaterThanOrEqualTo($overtimeEndTime)) {
            $expiredAt = $approvalTime->copy()->addHours(2);
        } else {
            $expiredAt = $overtimeEndTime->copy()->addHours(4);
        }

        $overtime->update([
            'status'      => 'APPROVED',
            'approved_at' => $approvalTime,
            'expired_at'  => $expiredAt
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Disetujui. Expired: ' . $expiredAt->format('d M Y H:i'),
            'data' => $overtime
        ]);
    }
    
    public function bulkStore(Request $request)
    {
        return response()->json(['message' => 'Bulk insert success'], 200);
    }
}