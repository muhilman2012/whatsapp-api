<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
                'dokumen_pendukung' => 'nullable', // Tidak hanya file, tetapi bisa berupa URL atau Base64
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

                // Jika laporan sudah ada tetapi tidak sedang diproses, kembalikan error 422
                return response()->json([
                    'success' => true,
                    'message' => 'Anda hanya dapat mengirim laporan sekali setiap 20 hari.',
                    'errors' => ['nik' => ['Anda hanya dapat mengirim laporan sekali setiap 20 hari.']]
                ], 200);
            }

            // Validasi dan Proses Dokumen Pendukung
            $filePath = null;
            if ($request->has('dokumen_pendukung')) {
                $dokumen = $request->dokumen_pendukung;

                if ($request->file('dokumen_pendukung')) {
                    // Jika file diunggah (PDF)
                    $filePath = $request->file('dokumen_pendukung')->store('dokumen');
                } elseif (filter_var($dokumen, FILTER_VALIDATE_URL)) {
                    // Jika URL valid (gambar publik)
                    if (preg_match('/\.(jpg|jpeg|png)$/', $dokumen)) {
                        $filePath = $dokumen; // Simpan URL langsung
                    } else {
                        return response()->json([
                            'success' => true,
                            'message' => 'Dokumen pendukung harus berupa file PDF atau URL gambar valid (jpg, jpeg, png).'
                        ], 200);
                    }
                } elseif ($this->isBase64($dokumen)) {
                    // Jika format Base64
                    $decodedFile = base64_decode($dokumen);

                    if (!$decodedFile) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Format Base64 tidak valid.'
                        ], 200);
                    }

                    // Simpan sebagai file di server
                    $filename = 'dokumen_' . time() . '.jpg'; // Asumsikan format gambar (jpg)
                    $filePath =  $filename;
                    file_put_contents(storage_path('app/dokumen/' . $filePath), $decodedFile);
                } else {
                    // Format tidak dikenal
                    return response()->json([
                        'success' => true,
                        'message' => 'Dokumen pendukung harus berupa file PDF, URL gambar, atau Base64 valid.'
                    ], 200);
                }
            }

            // Buat Laporan Baru
            $laporan = \App\Models\Laporan::create([
                'nomor_tiket' => str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT),
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
                'dokumen_pendukung' => $filePath,
                'sumber_pengaduan' => 'whatsapp',
            ]);

            // Response Berhasil
            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim.',
                'nomor_tiket' => $laporan->nomor_tiket,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons JSON
            return response()->json([
                'success' => true,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            // Tangkap error lainnya
            return response()->json([
                'success' => true,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
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

    // Helper untuk validasi Base64
    private function isBase64($string)
    {
        $decoded = base64_decode($string, true);
        if (!$decoded) {
            return false;
        }
        $encoded = base64_encode($decoded);
        return $encoded === $string;
    }

    public function getStatus($nomor_tiket)
    {
        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json([
                'success' => true,
                'message' => 'Laporan tidak ditemukan.',
            ], 200);
        }

        return response()->json([
            'success' => "true",
            'message' => "Laporan ditemukan",
            'nomor_tiket' => $laporan->nomor_tiket,
            'status' => $laporan->status,
            'created_at' => Carbon::parse($laporan->created_at)->format('d/m/Y'),
            'nama_lengkap' => $laporan->nama_lengkap,
            'judul' => $laporan->judul,
            'detail' => $laporan->detail,
            'lokasi' => $laporan->lokasi,
            'tanggal_kejadian' => $laporan->tanggal_kejadian,
            'tanggapan' => $laporan->tanggapan, // Tambahkan tanggapan
        ]);
    }
}