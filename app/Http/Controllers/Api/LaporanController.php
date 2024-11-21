<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

                        // Cek apakah NIK sudah mengirim laporan dalam 20 hari terakhir
                        $existingReport = \App\Models\Laporan::where('nik', $value)
                            ->where('created_at', '>=', now()->subDays(20))
                            ->first();

                        if ($existingReport) {
                            $fail('Anda hanya dapat mengirim laporan sekali setiap 20 hari.');
                        }
                    },
                ],
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'alamat_lengkap' => 'required|string',
                'judul' => 'required|string|max:255',
                'detail' => 'required|string',
                'lokasi' => 'nullable|string',
                'tanggal_kejadian' => 'nullable|date_format:d/m/Y',
                'dokumen_pendukung' => 'nullable|file|mimes:pdf|max:10240',
                'nomor_pengadu' => 'nullable|string|max:15',
                'email' => 'nullable|email|max:255',
            ]);

            // Mencegah inputan berbahaya (sanitize input)
            $input = $request->except(['dokumen_pendukung']);
            array_walk_recursive($input, function (&$value) {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            });

            // Proses Upload Dokumen Pendukung
            $filePath = null;
            if ($request->hasFile('dokumen_pendukung')) {
                $filePath = $request->file('dokumen_pendukung')->store('dokumen');
            }

            // Buat Laporan Baru
            $laporan = \App\Models\Laporan::create([
                'nomor_tiket' => str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT),
                'nama_lengkap' => $request->nama_lengkap,
                'nik' => $request->nik,
                'jenis_kelamin' => $request->jenis_kelamin,
                'alamat_lengkap' => $request->alamat_lengkap,
                'judul' => $request->judul,
                'detail' => $request->detail,
                'lokasi' => $request->lokasi,
                'tanggal_kejadian' => $request->tanggal_kejadian,
                'dokumen_pendukung' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dikirim.',
                'nomor_tiket' => $laporan->nomor_tiket,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tangkap error validasi dan kembalikan respons JSON
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Tangkap error lainnya
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getStatus($nomor_tiket)
    {
        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'nomor_tiket' => $laporan->nomor_tiket,
            'status' => $laporan->status,
            'judul' => $laporan->judul,
            'detail' => $laporan->detail,
            'lokasi' => $laporan->lokasi,
            'tanggal_kejadian' => $laporan->tanggal_kejadian,
            'tanggapan' => $laporan->tanggapan, // Tambahkan tanggapan
        ]);
    }
}
