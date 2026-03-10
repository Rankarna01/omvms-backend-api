<?php

namespace App\Imports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Membaca baris pertama sebagai nama kolom
use Maatwebsite\Excel\Concerns\WithValidation; // Untuk validasi data
use Maatwebsite\Excel\Concerns\SkipsEmptyRows; // Melewati baris yang kosong
use PhpOffice\PhpSpreadsheet\Shared\Date; // Untuk mengatasi format tanggal bawaan Excel
use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Handle format tanggal Excel. 
        // Excel sering mengubah tanggal menjadi angka seri (misal: 45000). Kita harus convert ke Y-m-d.
        $joinDate = null;
        if (isset($row['join_date'])) {
            if (is_numeric($row['join_date'])) {
                $joinDate = Carbon::instance(Date::excelToDateTimeObject($row['join_date']))->format('Y-m-d');
            } else {
                $joinDate = Carbon::parse($row['join_date'])->format('Y-m-d');
            }
        }

        return new Employee([
            // Kunci array (huruf kecil semua) diambil dari Header / Baris ke-1 di Excel
            'nik'           => $row['nik'],
            'full_name'     => $row['full_name'],
            'email'         => $row['email'],
            'phone'         => $row['phone'],
            'department_id' => $row['department_id'], 
            'shift_id'      => $row['shift_id'],
            'position'      => $row['position'],
            'join_date'     => $joinDate ?? Carbon::now()->format('Y-m-d'),
            // Jika is_active tidak diisi di excel, set default ke 1 (Aktif)
            'is_active'     => isset($row['is_active']) ? (int) $row['is_active'] : 1,
        ]);
    }

    /**
     * Validasi data dari Excel sebelum dimasukkan ke database
     */
    public function rules(): array
    {
        return [
            'nik'           => 'required|unique:employees,nik',
            'full_name'     => 'required|string',
            'email'         => 'required|email|unique:employees,email',
            'department_id' => 'required|exists:departments,id',
            'shift_id'      => 'required|exists:shifts,id',
        ];
    }
    
    /**
     * Ubah pesan error jika diperlukan (Opsional)
     */
    public function customValidationMessages()
    {
        return [
            'nik.unique' => 'Terdapat NIK yang sudah terdaftar di sistem.',
            'email.unique' => 'Terdapat Email yang sudah terdaftar di sistem.',
            'department_id.exists' => 'ID Department tidak valid / tidak ditemukan.',
            'shift_id.exists' => 'ID Shift tidak valid / tidak ditemukan.',
        ];
    }
}