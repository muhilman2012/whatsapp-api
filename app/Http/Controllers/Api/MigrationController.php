<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Laporan; 
use App\Models\Assignment; 
use App\Models\admins;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MigrationController extends Controller
{
    // Batasan default per halaman untuk migrasi
    const LIMIT = 500;

    public function getAdmins(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        $admins = admins::select([
            'id_admins', 
            'username',
            'nama',
            'email',
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

        // Ambil semua kolom yang dibutuhkan V2, termasuk ID lama (untuk V2 lookup)
        $laporans = Laporan::select([
            'id', // <-- ID Laporan V1 (Wajib untuk lookup di V2)
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

        $laporans->getCollection()->transform(function ($item) {
            $data = $item->toArray();
            
            $data['created_at'] = $item->created_at 
                ? $item->created_at->format('Y-m-d H:i:s') 
                : null;
                
            return $data;
        });

        return response()->json($laporans);
    }


    /**
     * 3. Mendapatkan data ASSIGNMENT V1.
     * Termasuk EMAIL user untuk lookup V2.
     */
    public function getAssignments(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        $assignments = Assignment::with([
            'assignedTo:id_admins,email', 
            'assignedBy:id_admins,email',
            'laporan:id,nomor_tiket'
        ])
        ->select([
            'laporan_id', // ID Laporan V1 (Wajib untuk lookup di V2)
            'analis_id', 
            'assigned_by', 
            'notes', 
            'created_at', 
            'updated_at'
        ])
        ->orderBy('laporan_id', 'asc')
        ->paginate($limit, ['*'], 'page', $request->get('page'));
        
        // Transformasi koleksi untuk menaikkan email Admin ke level utama array
        $assignments->getCollection()->transform(function ($item) {
            $analisEmail = $item->assignedTo->email ?? null;
            $assignedByEmail = $item->assignedBy->email ?? null;
            $nomorTiket = $item->laporan->nomor_tiket ?? null;

            return [
                'laporan_id' => $item->laporan_id,
                'nomor_tiket' => $nomorTiket,
                'notes' => $item->notes,
                // Menggunakan format standar DB V1
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null, 
                'updated_at' => $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : null,
                
                // Kunci utama untuk lookup di V2
                'analis_email' => $analisEmail,
                'assigned_by_email' => $assignedByEmail,
                'analis_id_v1' => $item->analis_id,
                'assigned_by_id_v1' => $item->assigned_by,
            ];
        });

        return response()->json($assignments);
    }

    /**
     * 4. Mendapatkan data LOGS V1 (Activity Logs).
     * Termasuk EMAIL user dan ID Laporan V1.
     */
    public function getLogs(Request $request)
    {
        $limit = $request->get('limit', self::LIMIT);

        $logs = Log::with(['user:id_admins,email', 'laporan:id,nomor_tiket']) 
            ->select([
                'id',              // ID Log V1
                'laporan_id',      // 🔥 ID Laporan V1 (Wajib untuk lookup Report V2)
                'user_id',         // Foreign Key User/Admin V1
                'activity',        // Deskripsi Aksi V1
                'created_at',
                'updated_at'
            ])
            ->orderBy('id', 'asc')
            ->paginate($limit, ['*'], 'page', $request->get('page')); 

        // Transformasi koleksi untuk menaikkan email Admin
        $logs->getCollection()->transform(function ($item) {
            
            $userEmail = $item->user->email ?? null;
            $nomorTiket = $item->laporan->nomor_tiket ?? null;

            return [
                'log_id_v1' => $item->id,
                'user_email_v1' => $userEmail, // Email untuk mapping User V2
                'action_description_v1' => $item->activity,
                
                // ID Laporan V1 untuk lookup Report V2
                'nomor_tiket' => $nomorTiket,
                'laporan_id_v1' => $item->laporan_id, 
                
                // Format created_at tanpa konversi timezone
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null, 
                'updated_at' => $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json($logs);
    }
}
