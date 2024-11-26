<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LaporanController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validasi Input
            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
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
                'alamat_lengkap' => 'required|string',
                'judul' => 'required|string|max:255',
                'detail' => 'required|string',
                'lokasi' => 'nullable|string',
                'tanggal_kejadian' => 'nullable|date_format:d/m/Y',
                'dokumen_pendukung' => 'nullable|url', // Validasi sebagai URL
                'nomor_pengadu' => 'nullable|string|max:15',
                'email' => 'nullable|email|max:255',
            ]);

            // Cek jika ada laporan sebelumnya dalam 20 hari terakhir
            $existingReport = \App\Models\Laporan::where('nik', $request->nik)
                ->where('created_at', '>=', now()->subDays(20))
                ->first();

            if ($existingReport) {
                // Jika laporan sebelumnya berstatus "Diproses", kembalikan response 200
                if ($existingReport->status === 'Diproses') {
                    return response()->json([
                        'success' => true,
                        'message' => 'Anda sudah memiliki laporan yang sedang diproses.',
                        'data' => [
                            'nomor_tiket' => $existingReport->nomor_tiket,
                            'status' => $existingReport->status,
                            'judul' => $existingReport->judul,
                            'detail' => $existingReport->detail,
                        ]
                    ], 200);
                }

                // Jika laporan sudah ada tetapi tidak sedang diproses, kembalikan error
                return response()->json([
                    'success' => true,
                    'message' => 'Anda hanya dapat mengirim laporan sekali setiap 20 hari.',
                    'errors' => ['nik' => ['Anda hanya dapat mengirim laporan sekali setiap 20 hari.']]
                ], 200);
            }

            // Nomor tiket unik
            $nomorTiket = str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);

            // Validasi URL dokumen pendukung
            $dokumenPendukung = null;
            if ($request->has('dokumen_pendukung') && $request->dokumen_pendukung) {
                // Simpan langsung URL yang dikirim dari Qontak/WhatsApp
                if (filter_var($request->dokumen_pendukung, FILTER_VALIDATE_URL)) {
                    $dokumenPendukung = $request->dokumen_pendukung;
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => 'Dokumen pendukung harus berupa URL valid.',
                    ], 200);
                }
            }

            // Buat Laporan Baru
            $laporan = \App\Models\Laporan::create([
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
                'dokumen_pendukung' => $dokumenPendukung, // Menyimpan URL saja
                'sumber_pengaduan' => 'whatsapp',
            ]);

            // Log laporan berhasil disimpan
            Log::info('Laporan berhasil disimpan:', ['laporan' => $laporan]);

            // Response Berhasil
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim.',
                'nomor_tiket' => $laporan->nomor_tiket,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons JSON
            Log::error('Validation Error:', $e->errors());
            return response()->json([
                'success' => true,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            // Tangkap error lainnya
            Log::error('Terjadi kesalahan:', ['exception' => $e->getMessage()]);
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }

    // Helper untuk validasi Base64
    private function isBase64($string)
    {
        $decoded = base64_decode($string, true);
        return $decoded !== false && base64_encode($decoded) === $string;
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
                'nomor_tiket' => 'nullable|string|max:7',
                'nik' => 'nullable|digits:16',
            ]);

            // Pastikan setidaknya salah satu parameter diberikan
            if (!$request->filled('nomor_tiket') && !$request->filled('nik')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Harap masukkan nomor tiket atau NIK untuk memeriksa status laporan.',
                ], 200);
            }

            // Cari laporan berdasarkan nomor_tiket atau nik
            $query = Laporan::query();

            if ($request->filled('nomor_tiket')) {
                $query->where('nomor_tiket', $request->nomor_tiket);
            }

            if ($request->filled('nik')) {
                $query->where('nik', $request->nik);
            }

            $laporan = $query->first();

            // Jika laporan tidak ditemukan
            if (!$laporan) {
                return response()->json([
                    'success' => true,
                    'message' => 'Laporan tidak ditemukan dengan nomor tiket atau NIK yang diberikan.',
                ], 200);
            }

            // Jika laporan ditemukan
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
}