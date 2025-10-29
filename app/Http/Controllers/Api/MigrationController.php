<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Laporan; 
use App\Models\Assignment; 
use App\Models\admins; 
use Illuminate\Database\Eloquent\Builder;

class MigrationController extends Controller
{
    // Batasan default per halaman untuk migrasi
    const LIMIT = 500; 

    /**
     * 1. Mendapatkan data ADMINS/USERS V1.
     * Kunci utama: 'id_admins'.
     */
    public function getAdmins(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        // Ambil data admin dengan kolom-kolom yang diperlukan
        $admins = admins::select([
            'id_admins',     // Kunci utama Admin V1 (akan menjadi v1_admin_id di V2, jika digunakan)
            'username',
            'nama',
            'email',         // WAJIB, untuk lookup Assignment di V2
            'password',      
            'phone',
            'address',
            'role',
            'jabatan',
            'deputi',
            'unit',
            'created_at',
            'updated_at'
        ])
        ->orderBy('id_admins', 'asc')
        ->paginate($limit, ['*'], 'page', $request->get('page'));

        return response()->json($admins);
    }


    /**
     * 2. Mendapatkan data LAPORAN V1.
     * Kunci utama: 'id'.
     */
    public function getReports(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        // Ambil semua kolom yang dibutuhkan V2, termasuk ID lama sebagai referensi
        $laporans = Laporan::select([
            'id', // <-- KUNCI UTAMA LAPORAN V1 (akan menjadi v1_report_id di V2)
            'nomor_tiket', 
            'judul', 
            'detail', 
            'created_at', 
            'tanggal_kejadian', 
            'lokasi', 
            'sumber_pengaduan', 
            'status', 
            'tanggapan', 
            'nik', 
            'nama_lengkap', 
            'nomor_pengadu', 
            'email', 
            'alamat_lengkap', 
            'kategori', 
            'disposisi', 
            'disposisi_terbaru',
            'lembar_kerja_analis'
        ])
        ->orderBy('id', 'asc')
        ->paginate($limit, ['*'], 'page', $request->get('page')); 

        return response()->json($laporans);
    }


    /**
     * 3. Mendapatkan data ASSIGNMENT V1 dengan menyertakan EMAIL untuk lookup di V2.
     */
    public function getAssignments(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        // Gunakan eager loading (with) untuk mendapatkan data email Admin yang berelasi
        $assignments = Assignment::with([
            // Ambil ID dan Email dari Analis (assignedTo)
            'assignedTo:id_admins,email', 
            // Ambil ID dan Email dari Pemberi Tugas (assignedBy)
            'assignedBy:id_admins,email' 
        ])
        ->select([
            'laporan_id',    
            'analis_id',     
            'assigned_by',   
            'notes', 
            'created_at', 
            'updated_at'
        ])
        ->orderBy('laporan_id', 'asc')
        ->paginate($limit, ['*'], 'page', $request->get('page'));
        
        // Transformasi koleksi untuk menaikkan email ke level utama array
        $assignments->getCollection()->transform(function ($item) {
            
            // Ambil email, pastikan tidak error jika relasi kosong
            $analisEmail = $item->assignedTo->email ?? null;
            $assignedByEmail = $item->assignedBy->email ?? null;

            return [
                'laporan_id' => $item->laporan_id, // ID Laporan V1
                'notes' => $item->notes,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                // Kunci utama untuk lookup di V2
                'analis_email' => $analisEmail,
                'assigned_by_email' => $assignedByEmail,
                // ID lama (untuk debugging/fallback)
                'analis_id_v1' => $item->analis_id,
                'assigned_by_id_v1' => $item->assigned_by,
            ];
        });

        return response()->json($assignments);
    }
}
