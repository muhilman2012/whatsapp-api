<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use App\Models\Log;
use App\Models\admins;
use App\Models\Assignment;
use App\Models\Notification;
use App\Models\Dokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Exception;

class LaporanController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'nama_lengkap' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                    if (!preg_match('/^[\pL\s\-]+$/u', $value)) {
                        $fail('Nama lengkap hanya boleh mengandung huruf, spasi, dan tanda hubung.');
                    }
                }],
                'nik' => ['required', 'digits:16', function ($attribute, $value, $fail) {
                    if (!preg_match('/^\d{16}$/', $value)) {
                        $fail('Format NIK tidak valid. Harus berupa 16 digit angka.');
                    }
                }],
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'alamat_lengkap' => ['required', 'string', function ($attribute, $value, $fail) {
                    if (preg_match('[$$$${}<>]', $value)) {
                        $fail('Alamat lengkap tidak boleh mengandung karakter tidak valid.');
                    }
                }],
                'judul' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                    if (preg_match('/[$$$${}<>]/', $value)) {
                        $fail('Judul tidak boleh mengandung karakter tidak valid.');
                    }
                }],
                'detail' => 'required|string|max:10000',
                'lokasi' => ['nullable', 'string', function ($attribute, $value, $fail) {
                    if (preg_match('[$$$${}<>]', $value)) {
                        $fail('Lokasi tidak boleh mengandung karakter tidak valid.');
                    }
                }],
                'tanggal_kejadian' => 'nullable|date_format:d/m/Y',
                'dokumen_ktp' => 'required|string',
                'dokumen_kk' => 'required|string',
                'dokumen_skuasa' => 'nullable|string',
                'dokumen_pendukung' => 'required|string',
                'nomor_pengadu' => 'nullable|string|max:15',
                'email' => ['nullable', 'email', 'max:255', function ($attribute, $value, $fail) {
                    if (filter_var($value, FILTER_VALIDATE_EMAIL) === true) {
                        $fail('Email pengadu tidak valid.');
                    }
                }],
            ]);

            // Cek duplikat laporan
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

            // Generate nomor tiket
            $nomorTiket = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);

            // Simpan file dokumen ke storage dan ambil URL-nya
            $pathKtp = $this->simpanDokumenBase64($request->dokumen_ktp, $nomorTiket, 'ktp');
            $pathKk = $this->simpanDokumenBase64($request->dokumen_kk, $nomorTiket, 'kk');
            $pathSkuasa = $request->filled('dokumen_skuasa') ? $this->simpanDokumenBase64($request->dokumen_skuasa, $nomorTiket, 'skuasa') : null;
            $pathPendukung = $this->simpanDokumenBase64($request->dokumen_pendukung, $nomorTiket, 'pendukung');

            // Simpan laporan ke database
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
                'sumber_pengaduan' => 'whatsapp',

                // Simpan link dokumen ke kolom laporan
                'dokumen_ktp' => $pathKtp,
                'dokumen_kk' => $pathKk,
                'dokumen_skuasa' => $pathSkuasa,
                'dokumen_pendukung' => $pathPendukung,
            ]);

            Log::create([
                'laporan_id' => $laporan->id,
                'activity' => "Laporan baru dari Whatsapp",
                'user_id' => auth()->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim.',
                'nomor_tiket' => $laporan->nomor_tiket,
                'kategori' => $laporan->kategori,
                'disposisi' => $laporan->disposisi,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->info('Error Validasi:', $e->errors());
            return response()->json([
                'success' => true,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            logger()->info('Error Saat Menyimpan Laporan:', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }
  
    // Helper untuk validasi Base64  
    private function simpanDokumenBase64($base64, $nomorTiket, $jenis)
    {
        if (!$base64 || !$this->isBase64($base64)) {
            throw new \Exception("Format base64 tidak valid untuk dokumen $jenis.");
        }

        $decoded = base64_decode(preg_replace('#^data:.*;base64,#', '', $base64));
        $ext = $this->getExtensionFromBase64($base64) ?? 'pdf';
        $filename = "{$nomorTiket}_{$jenis}." . $ext;

        $path = "dokumen/{$filename}"; // ⬅️ tanpa awalan "public/"
        $result = Storage::disk('public')->put($path, $decoded); // ⬅️ Gunakan disk 'public'

        if (!$result) {
            throw new \Exception("Gagal menyimpan dokumen ke storage.");
        }

        return $path; // hanya kembalikan path relatif dari storage/public
    }

    private function getExtensionFromBase64($base64)
    {
        if (str_starts_with($base64, 'data:image/jpeg')) return 'jpg';
        if (str_starts_with($base64, 'data:image/png')) return 'png';
        if (str_starts_with($base64, 'data:application/pdf')) return 'pdf';
        return null;
    }

    private function isBase64($string)
    {
        return (bool) preg_match('/^data:\w+\/[\w\-\+]+;base64,/', $string);
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
  
        // Cari laporan terakhir berdasarkan NIK dengan status selain Penanganan Selesai
        $existingReport = Laporan::where('nik', $nik)  
            ->where('status', '!=', 'Penanganan Selesai')
            //->where('created_at', '>=', now()->subDays(20))   // Cari laporan terakhir berdasarkan NIK dalam 20 hari terakhir   
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
            $request->validate([
                'nomor_tiket' => 'required|string|max:7',
                'dokumen_tambahan' => 'required|string', // base64
            ]);

            $laporan = Laporan::where('nomor_tiket', $request->nomor_tiket)->first();

            if (!$laporan) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak ditemukan dengan nomor tiket yang diberikan.',
                ], 200);
            }

            // Periksa apakah status laporan adalah "Menunggu kelengkapan Data dukung dari Pelapor"Add commentMore actions
            if ($laporan->status !== 'Menunggu kelengkapan data dukung dari Pelapor') {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak berada dalam status yang sesuai untuk mengirim dokumen tambahan.',
                ], 200);
            }
            
            // Simpan dokumen tambahan
            $webPath = $this->simpanDokumenBase64($request->dokumen_tambahan, $laporan->nomor_tiket, 'tambahan_' . now()->format('Ymd_His'));

            $filename = basename($webPath); // Ambil nama file saja (tanpa path)

            // Update laporan: status tetap diperbarui, tapi `dokumen_tambahan` hanya berisi nama file terakhir
            $laporan->dokumen_tambahan = $filename;
            $laporan->status = 'Proses verifikasi dan telaah';
            $laporan->tanggapan = 'Laporan pengaduan Saudara dalam proses verifikasi & penelaahan.';
            $laporan->save();

            // Log
            Log::create([
                'laporan_id' => $laporan->id,
                'activity' => "Pengadu mengirim Dokumen Tambahan Baru",
                'user_id' => auth()->user()->id,
            ]);

            // Kirim notifikasi ke analis
            $assignments = Assignment::where('laporan_id', $laporan->id)->get();

            foreach ($assignments as $assignment) {
                // Analis
                $analis = $assignment->assignedTo;
                Notification::create([
                    'assigner_id' => auth()->user()->id,
                    'assignee_id' => $analis->id_admins,
                    'laporan_id' => $laporan->id,
                    'message' => 'Pengadu telah mengirim Dokumen Baru',
                    'is_read' => false,
                ]);

                // Asdep yang meng-assign
                $assignedBy = $assignment->assignedBy;
                if ($assignedBy && $assignedBy->role === 'asdep') {
                    Notification::create([
                        'assigner_id' => auth()->user()->id,
                        'assignee_id' => $assignedBy->id_admins,
                        'laporan_id' => $laporan->id,
                        'message' => 'Pengadu telah mengirim Dokumen Baru',
                        'is_read' => false,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Dokumen tambahan berhasil dikirim.',
                'data' => [
                    'nomor_tiket' => $laporan->nomor_tiket,
                    'nama_file' => $filename,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => true,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            logger()->info('Error Saat Mengirim Dokumen Tambahan:', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function cekTiketUntukDokumen(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'nomor_tiket' => 'required|string|max:7', // Nomor tiket wajib diisi
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

            // Periksa apakah status laporan adalah "Menunggu kelengkapan data dukung dari Pelapor"
            if ($laporan->status !== 'Menunggu kelengkapan data dukung dari Pelapor') {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak berada dalam status yang sesuai untuk mengirim dokumen tambahan.',
                ], 200);
            }

            // Jika status laporan sesuai
            return response()->json([
                'success' => true,
                'message' => 'Nomor tiket ini diperbolehkan untuk mengirim dokumen tambahan.',
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
            logger()->info('Error Saat Mengecek Tiket:', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }
}