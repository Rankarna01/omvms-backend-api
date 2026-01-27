<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $shifts
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shift_name'   => 'required|string|max:100',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i',
            'allow_meal'   => 'required|boolean',
            'lock_request' => 'required|boolean', // Validasi Logic Baru
            'description'  => 'nullable|string'
        ]);

        $shift = Shift::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil dibuat',
            'data' => $shift
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json(['message' => 'Shift tidak ditemukan'], 404);
        }

        $request->validate([
            'shift_name'   => 'required|string|max:100',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i',
            'allow_meal'   => 'required|boolean',
            'lock_request' => 'required|boolean',
            'description'  => 'nullable|string'
        ]);

        $shift->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil diperbarui',
            'data' => $shift
        ]);
    }

    public function destroy($id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json(['message' => 'Shift tidak ditemukan'], 404);
        }

        if ($shift->employees()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal hapus. Shift ini sedang digunakan oleh karyawan.'
            ], 400);
        }

        $shift->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Shift berhasil dihapus'
        ]);
    }
}