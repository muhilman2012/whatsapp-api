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
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nik' => 'required|digits:16',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat_lengkap' => 'required|string',
            'jenis_laporan' => 'required|in:Pengaduan,Aspirasi,Permintaan Informasi',
            'judul' => 'required|string|max:255',
            'detail' => 'required|string',
            'lokasi' => 'nullable|string',
            'tanggal_kejadian' => 'nullable|date|required_if:jenis_laporan,Pengaduan',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf|max:10240',
            'nomor_pengadu' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
        ]);

        $filePath = null;
        if ($request->hasFile('dokumen_pendukung')) {
            $filePath = $request->file('dokumen_pendukung')->store('dokumen');
        }

        $laporan = Laporan::create([
            'nomor_tiket' => str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT),
            'nomor_pengadu' => $request->nomor_pengadu,
            'email' => $request->email,
            'nama_lengkap' => $request->nama_lengkap,
            'nik' => $request->nik,
            'jenis_kelamin' => $request->jenis_kelamin,
            'alamat_lengkap' => $request->alamat_lengkap,
            'jenis_laporan' => $request->jenis_laporan,
            'judul' => $request->judul,
            'detail' => $request->detail,
            'lokasi' => $request->lokasi,
            'tanggal_kejadian' => $request->tanggal_kejadian,
            'dokumen_pendukung' => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim.',
            'nomor_tiket' => $laporan->nomor_tiket
        ]);
    }

    public function getStatus($nomor_tiket)
    {
        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'nomor_tiket' => $laporan->nomor_tiket,
            'status' => $laporan->status,
            'jenis_laporan' => $laporan->jenis_laporan,
            'judul' => $laporan->judul,
            'detail' => $laporan->detail,
            'lokasi' => $laporan->lokasi,
            'tanggal_kejadian' => $laporan->tanggal_kejadian,
            'dokumen_pendukung' => $laporan->dokumen_pendukung,
            'tanggapan' => $laporan->tanggapan,
        ]);
    }

    public function updateStatus(Request $request, $nomor_tiket)
    {
        $request->validate([
            'status' => 'required|string|max:255',
            'tanggapan' => 'nullable|string',
        ]);

        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan.'
            ], 404);
        }

        $laporan->update([
            'status' => $request->status,
            'tanggapan' => $request->tanggapan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diperbarui.',
            'status' => $laporan->status,
            'tanggapan' => $laporan->tanggapan
        ]);
    }
}
