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
                        if (preg_match('/[$$$${}<>]/', $value)) {
                            $fail('Lokasi tidak boleh mengandung karakter tidak valid.');
                        }
                    },
                ],
                'tanggal_kejadian' => 'nullable|date_format:d/m/Y',
                'dokumen_ktp' => 'required|url', // KTP harus berupa URL yang valid
                'dokumen_kk' => 'nullable|url', // KK harus berupa URL yang valid
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
            $existingReport = \App\Models\Laporan::where('nik', $request->nik)
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
                'dokumen_ktp' => $request->dokumen_ktp, // Simpan URL dokumen KTP
                'dokumen_kk' => $request->dokumen_kk, // Simpan URL dokumen KK
                'dokumen_skuasa' => $request->dokumen_skuasa, // Simpan URL dokumen kuasa
                'dokumen_pendukung' => $request->dokumen_pendukung, // Simpan URL dokumen pendukung
                'sumber_pengaduan' => 'whatsapp',
            ]);

            // Log laporan berhasil disimpan
            \Log::info('Laporan berhasil disimpan:', [
                'nomor_tiket' => $laporan->nomor_tiket,
                'kategori' => $laporan->kategori,
                'disposisi' => $laporan->disposisi,
            ]);

            // Kembalikan respons sukses
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim.',
                'nomor_tiket' => $laporan->nomor_tiket,
                'kategori' => $laporan->kategori,
                'disposisi' => $laporan->disposisi,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons
            \Log::error('Error Validasi:', $e->errors());
            return response()->json([
                'success' => true,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            // Tangkap error lainnya
            \Log::error('Error Saat Menyimpan Laporan:', ['exception' => $e->getMessage()]);
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
}