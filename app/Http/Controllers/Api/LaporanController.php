<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Log;
use App\Models\admins;
use App\Models\Assignment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class LaporanController extends Controller
{
    public function store(Request $request)  
    {  
        try {
            // Validasi Input  
            $request->validate([  
                'nama_lengkap' => [  
                    'required',  
                    'string',  
                    'max:255',  
                    function ($attribute, $value, $fail) {  
                        if (!preg_match('/^[\pL\s\-]+$/u', $value)) {  
                            $fail('Nama lengkap hanya boleh mengandung huruf, spasi, dan tanda hubung.');  
                        }  
                    },  
                ],  
                'nik' => [  
                    'required',  
                    'digits:16',  
                    function ($attribute, $value, $fail) {  
                        if (!preg_match('/^\d{16}$/', $value)) {  
                            $fail('Format NIK tidak valid. Harus berupa 16 digit angka.');  
                        }  
                    },  
                ],  
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',  
                'alamat_lengkap' => [  
                    'required',  
                    'string',  
                    function ($attribute, $value, $fail) {  
                        if (preg_match('[$$$${}<>]', $value)) {  
                            $fail('Alamat lengkap tidak boleh mengandung karakter tidak valid.');  
                        }  
                    },  
                ],  
                'judul' => [  
                    'required',  
                    'string',  
                    'max:255',  
                    function ($attribute, $value, $fail) {  
                        if (preg_match('/[$$$${}<>]/', $value)) {  
                            $fail('Judul tidak boleh mengandung karakter tidak valid.');  
                        }  
                    },  
                ],  
                'detail' => 'required|string|max:10000',  
                'lokasi' => [  
                    'nullable',  
                    'string',  
                    function ($attribute, $value, $fail) {  
                        if (preg_match('[$$$${}<>]', $value)) {  
                            $fail('Lokasi tidak boleh mengandung karakter tidak valid.');  
                        }  
                    },  
                ],  
                'tanggal_kejadian' => 'nullable|date_format:d/m/Y',  
                'dokumen_ktp' => 'required|url', // KTP harus berupa URL yang valid  
                'dokumen_kk' => 'required|url', // KK harus berupa URL yang valid  
                'dokumen_skuasa' => 'nullable|url', // Opsional, harus berupa URL yang valid  
                'dokumen_pendukung' => 'required|url', // Harus berupa URL yang valid  
                'nomor_pengadu' => 'nullable|string|max:15',  
                'email' => [  
                    'nullable',  
                    'email',  
                    'max:255',  
                    function ($attribute, $value, $fail) {  
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) === true) {  
                            $fail('Email pengadu tidak valid.');  
                        }  
                    },  
                ],  
            ]);
  
            // Periksa apakah ada laporan sebelumnya dengan NIK yang sama dalam 20 hari terakhir  
            $existingReport = Laporan::where('nik', $request->nik)  
                ->where('created_at', '>=', now()->subDays(20))  
                ->first();
  
            if ($existingReport) {
                if ($existingReport->status === 'Proses verifikasi dan telaah') {  
                    return response()->json([  
                        'success' => true,  
                        'message' => 'Anda sudah memiliki laporan yang sedang diverifikasi dan telaah.',  
                        'data' => [  
                            'nomor_tiket' => $existingReport->nomor_tiket,  
                            'status' => $existingReport->status,  
                            'judul' => $existingReport->judul,  
                            'detail' => $existingReport->detail,  
                        ],  
                    ], 200);  
                }
  
                return response()->json([  
                    'success' => true,  
                    'message' => 'Anda hanya dapat mengirim laporan sekali setiap 20 hari.',  
                    'errors' => ['nik' => ['Anda hanya dapat mengirim laporan sekali setiap 20 hari.']],  
                ], 200);  
            }
  
            // Generate nomor tiket unik  
            $nomorTiket = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);  
  
            // Simpan laporan baru dengan URL dokumen  
            $laporan = Laporan::create([  
                'nomor_tiket' => $nomorTiket,  
                'nama_lengkap' => $request->nama_lengkap,  
                'nomor_pengadu' => $request->nomor_pengadu,  
                'email' => $request->email,  
                'nik' => $request->nik,  
                'jenis_kelamin' => $request->jenis_kelamin,  
                'alamat_lengkap' => $request->alamat_lengkap,  
                'judul' => $request->judul,  
                'detail' => $request->detail,  
                'lokasi' => $request->lokasi,  
                'tanggal_kejadian' => $request->tanggal_kejadian,  
                'dokumen_ktp' => $request->dokumen_ktp, // Simpan URL dokumen KTP  
                'dokumen_kk' => $request->dokumen_kk, // Simpan URL dokumen KK  
                'dokumen_skuasa' => $request->dokumen_skuasa, // Simpan URL dokumen kuasa  
                'dokumen_pendukung' => $request->dokumen_pendukung, // Simpan URL dokumen pendukung  
                'sumber_pengaduan' => 'whatsapp',  
            ]);

            // Kirim data ke API  
            $apiResponse = $this->sendToApi($laporan);
  
            if ($apiResponse['success'] && isset($apiResponse['data']['complaint_id'])) {
                // Simpan complaint_id dari API eksternal ke database
                $laporan->update([
                    'complaint_id' => $apiResponse['data']['complaint_id'],
                ]);
            }

            // Log laporan berhasil disimpan menggunakan logger
            logger()->info('Laporan berhasil disimpan:', [  
                'nomor_tiket' => $laporan->nomor_tiket,  
                'kategori' => $laporan->kategori,  
                'disposisi' => $laporan->disposisi,
                'complaint_id' => $laporan->complaint_id,
            ]);
  
            // Menyimpan log aktivitas
            Log::create([
                'laporan_id' => $laporan->id,
                'activity' => "Laporan baru dari Whatsapp",
                'user_id' => auth()->user()->id,
            ]);

            // Kembalikan respons sukses  
            return response()->json([  
                'success' => true,  
                'message' => 'Laporan berhasil dikirim.',  
                'nomor_tiket' => $laporan->nomor_tiket,  
                'kategori' => $laporan->kategori,  
                'disposisi' => $laporan->disposisi,
                'complaint_id' => $laporan->complaint_id,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {  
            // Tangkap error validasi dan kembalikan respons  
            logger()->info('Error Validasi:', $e->errors());  
            return response()->json([  
                'success' => true,  
                'message' => 'Validasi gagal.',  
                'errors' => $e->errors(),  
            ], 200);
        } catch (\Exception $e) {  
            // Tangkap error lainnya  
            logger()->info('Error Saat Menyimpan Laporan:', ['exception' => $e->getMessage()]);  
            return response()->json([  
                'success' => true,  
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),  
            ], 200);
        }  
    }  
  
    private function sendToApi($laporan)    
    {    
        $url = 'https://api-splp.layanan.go.id/sandbox-konsolidasi/1.0/complaints/complaint';    
        $authToken = '$2y$10$unfC.K/m0piy3RucCtlZIe4gAV1jHsDvUvyEQhSogljb.sP0axIKa';    
        $token = '{14YJ3OQR-QYMC-MXVO-QVUX-HPTQJYCCS8TT}';    
    
        // Siapkan data yang akan dikirim    
        $data = [    
            'title' => $laporan->judul,    
            'content' =>"Nomor Tiket pada Aplikasi LMW: " . $laporan->nomor_tiket .
                        " , Nama Lengkap: " . $laporan->nama_lengkap .   
                        " , NIK: " . $laporan->nik .
                        " , Alamat Lengkap: " . $laporan->alamat_lengkap .  
                        " , Detail Laporan: " . $laporan->detail .  
                        " , Lokasi: " . $laporan->lokasi .
                        " , Dokumen KTP: " . $laporan->dokumen_ktp .  
                        " , Dokumen KK: " . $laporan->dokumen_kk .  
                        " , Dokumen Kuasa: " . $laporan->dokumen_skuasa .  
                        " , Dokumen Pendukung: " . $laporan->dokumen_pendukung,    
            'channel' => 26,
            'is_new_user_slider' => true,
            'emailUser' => $laporan->email,
            'nameUser' => $laporan->nama_lengkap,
            'phoneUser' => $laporan->nomor_pengadu,
            'is_disposisi_slider' => true,
            'classification_id' => 6,
            'disposition_id' => 151345,
            'category_id' => 15, //apakah boleh bebas
            'priority_program_id' => null,
            'location_id' => 34, //apakah boleh bebas (34 Nasional)
            'community_id' => null, //apakah boleh bebas
            'date_of_incident' => $laporan->tanggal_kejadian,
            'copy_externals' => null, // Apakah ini harus?
            'info_disposition' => 'Ini keterangan disposisi.', 
            'info_attachments' => '[66]',
            'tags_raw' => '#pengaduanwhatsapp', //apakah boleh bebas
            'is_approval' => true,
            'is_anonymous' => true,
            'is_secret' => true,
            'is_priority' => true,
            'attachments' => '[4199656]',
        ];
    
        try {
            // Kirim permintaan POST ke API eksternal
            $response = Http::withHeaders([
                'auth' => 'Bearer ' . $authToken,
                'token' => $token,
                'Content-Type' => 'application/json',
            ])->post($url, $data);
    
            // Periksa apakah respons berhasil
            if ($response->successful()) {
                $responseData = $response->json();

                // Tangani jika API mengembalikan error
                logger()->info('Mengirim Data ke API External Berhasil.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            } else {
                // Tangani jika API mengembalikan error
                logger()->info('API eksternal mengembalikan error.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
    
                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }
        } catch (\Exception $e) {
            // Tangani jika ada kesalahan saat mengirim permintaan
            logger()->info('Terjadi kesalahan saat mengirim data ke API eksternal.', [
                'exception' => $e->getMessage(),
            ]);
    
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }  
    }
  
    // Helper untuk validasi Base64  
    private function isBase64($string)  
    {  
        $decoded = base64_decode($string, true);  
        return $decoded !== true && base64_encode($decoded) === $string;  
    }  
  
    // Method untuk validasi NIK  
    public function validateNik($nik)  
    {  
        // Cek apakah NIK valid (16 digit angka)  
        if (!preg_match('/^\d{16}$/', $nik)) {  
            return response()->json([  
                'success' => true,  
                'message' => 'Format NIK tidak valid. Harus berupa 16 digit angka.'  
            ], 200);  
        }  
  
        // Cari laporan terakhir berdasarkan NIK dalam 20 hari terakhir  
        $existingReport = Laporan::where('nik', $nik)  
            ->where('created_at', '>=', now()->subDays(20))  
            ->first();  
  
        if ($existingReport) {  
            // Jika laporan ditemukan  
            return response()->json([  
                'success' => true,  
                'message' => 'Laporan ditemukan.',  
                'data' => [  
                    'nomor_tiket' => $existingReport->nomor_tiket,  
                    'status' => $existingReport->status,  
                    'judul' => $existingReport->judul,  
                    'detail' => $existingReport->detail  
                ]  
            ], 200);  
        }  
  
        // Jika tidak ada laporan dalam 20 hari terakhir  
        return response()->json([  
            'success' => true,  
            'message' => 'Tidak ada laporan yang sedang diproses untuk NIK ini.'  
        ], 200);  
    }  
  
    public function getStatus(Request $request)  
    {  
        try {  
            // Validasi Input  
            $request->validate([  
                'nomor_tiket' => 'required|string|max:7', // Nomor tiket wajib diisi  
                'nik' => 'nullable|digits:16', // NIK opsional pada langkah pertama  
            ]);  
  
            // Cari laporan berdasarkan nomor_tiket  
            $laporan = Laporan::where('nomor_tiket', $request->nomor_tiket)->first();  
  
            // Jika laporan tidak ditemukan  
            if (!$laporan) {  
                return response()->json([  
                    'success' => true,  
                    'message' => 'Laporan tidak ditemukan dengan nomor tiket yang diberikan.',  
                ], 200);  
            }  
  
            // Jika NIK diberikan, validasi kecocokan NIK  
            if ($request->filled('nik')) {  
                if ($laporan->nik !== $request->nik) {  
                    return response()->json([  
                        'success' => true,  
                        'message' => 'NIK tidak cocok dengan nomor tiket yang diberikan.',  
                    ], 200);  
                }  
            } else {  
                // Jika NIK tidak diberikan, minta pengguna untuk memasukkan NIK  
                return response()->json([  
                    'success' => true,  
                    'message' => 'Silakan masukkan NIK untuk melanjutkan.',  
                    'data' => [  
                        'nomor_tiket' => $laporan->nomor_tiket,  
                        'status' => $laporan->status,  
                    ],  
                ], 200);  
            }  
  
            // Jika laporan ditemukan dan NIK cocok  
            return response()->json([  
                'success' => true,  
                'message' => 'Laporan ditemukan.',  
                'data' => [  
                    'nomor_tiket' => $laporan->nomor_tiket,  
                    'status' => $laporan->status,  
                    'created_at' => Carbon::parse($laporan->created_at)->format('d/m/Y'),  
                    'nama_lengkap' => $laporan->nama_lengkap,  
                    'judul' => $laporan->judul,  
                    'detail' => $laporan->detail,  
                    'lokasi' => $laporan->lokasi,  
                    'tanggal_kejadian' => $laporan->tanggal_kejadian,  
                    'tanggapan' => $laporan->tanggapan,  
                ],  
            ]);  
        } catch (\Illuminate\Validation\ValidationException $e) {  
            // Error Validasi  
            return response()->json([  
                'success' => true,  
                'message' => 'Validasi gagal.',  
                'errors' => $e->errors(),  
            ], 200);  
        } catch (\Exception $e) {  
            // Error lainnya  
            return response()->json([  
                'success' => true,  
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),  
            ], 200);  
        }  
    }

    public function kirimDokumenTambahan(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'nomor_tiket' => 'required|string|max:7', // Nomor tiket wajib diisi
                'dokumen_tambahan' => 'required|url', // Harus berupa URL yang valid
            ]);

            // Cari laporan berdasarkan nomor tiket
            $laporan = Laporan::where('nomor_tiket', $request->nomor_tiket)->first();

            // Jika laporan tidak ditemukan
            if (!$laporan) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak ditemukan dengan nomor tiket yang diberikan.',
                ], 200);
            }

            // Periksa apakah dokumen tambahan sudah terisi
            if ($laporan->dokumen_tambahan) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anda sudah mengirim Dokumen Tambahan.',
                ], 200);
            }

            // Periksa apakah status laporan adalah "Menunggu kelengkapan Data dukung dari Pelapor"
            if ($laporan->status !== 'Menunggu kelengkapan Data dukung dari Pelapor') {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak berada dalam status yang sesuai untuk mengirim dokumen tambahan.',
                ], 200);
            }

            // Simpan dokumen tambahan
            $laporan->dokumen_tambahan = $request->dokumen_tambahan;
            $laporan->save();

            // Log dokumen tambahan berhasil dikirim
            logger()->info('Dokumen tambahan berhasil dikirim', [
                'nomor_tiket' => $laporan->nomor_tiket,
                'dokumen_tambahan' => $laporan->dokumen_tambahan,
            ]);

            // Menyimpan log aktivitas
            Log::create([
                'laporan_id' => $laporan->id,
                'activity' => "Pengadu mengirim Dokumen Tambahan Baru",
                'user_id' => auth()->user()->id,
            ]);

            // Mengambil ID analis yang ditugaskan pada laporan ini
            $assignments = Assignment::where('laporan_id', $laporan->id)->get();
            
            // Kirimkan notifikasi kepada analis yang terlibat
            foreach ($assignments as $assignment) {
                $analis = $assignment->assignedTo;

                // Kirim notifikasi kepada analis
                Notification::create([
                    'assigner_id' => auth()->user()->id,  // ID pengirim
                    'assignee_id' => $analis->id_admins,  // ID penerima (analis)
                    'laporan_id' => $laporan->id,  // ID laporan
                    'message' => 'Pengadu telah mengirim Dokumen Baru',
                    'is_read' => false,  // Notifikasi belum dibaca
                ]);
            }

            // Kirimkan notifikasi ke asdep yang meng-assign analis tersebut
            foreach ($assignments as $assignment) {
                $assignedBy = $assignment->assignedBy; // Ambil asdep yang meng-assign

                if ($assignedBy && $assignedBy->role === 'asdep') { // Pastikan yang meng-assign adalah asdep
                    // Kirim notifikasi kepada asdep
                    Notification::create([
                        'assigner_id' => auth()->user()->id,  // ID pengirim
                        'assignee_id' => $assignedBy->id_admins,  // ID penerima (asdep)
                        'laporan_id' => $laporan->id,  // ID laporan
                        'message' => 'Pengadu telah mengirim Dokumen Baru',
                        'is_read' => false,  // Notifikasi belum dibaca
                    ]);
                }
            }

            // Kembalikan respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Dokumen tambahan berhasil dikirim.',
                'data' => [
                    'nomor_tiket' => $laporan->nomor_tiket,
                    'dokumen_tambahan' => $laporan->dokumen_tambahan,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons
            return response()->json([
                'success' => true,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            // Tangkap error lainnya
            logger()->info('Error Saat Mengirim Dokumen Tambahan:', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }
}