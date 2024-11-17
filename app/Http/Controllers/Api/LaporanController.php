<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Laporan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // Simpan laporan baru
    public function store(Request $request)
    {
        $request->validate([
            'namalengkap'       => 'required|string',
            'nik'               => 'required|digits:16',
            'jenis_kelamin'     => 'required|in:L,P',
            'alamatlengkap'     => 'required|string',
            'jenis_laporan'     => 'required|in:Pengaduan,Aspirasi,Permintaan Informasi',
            'judul'             => 'required|string',
            'detail'            => 'required|string',
            'lokasi'            => 'nullable|string',
            'tanggalkejadian'   => 'nullable|required_if:jenis_laporan,Pengaduan|date',
            'dokumenpendukung'  => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $file = $request->file('dokumenpendukung');
        $filePath = $file ? $file->store('dokumen') : null;

        $laporan = Laporan::create([
            'namalengkap'       => $request->namalengkap,
            'nik'               => $request->nik,
            'jenis_kelamin'     => $request->jenis_kelamin,
            'alamatlengkap'     => $request->alamatlengkap,
            'jenis_laporan'     => $request->jenis_laporan,
            'judul'             => $request->judul,
            'detail'            => $request->detail,
            'lokasi'            => $request->lokasi,
            'tanggalkejadian'   => $request->tanggalkejadian,
            'dokumenpendukung'  => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'nomor_tiket' => $laporan->nomor_tiket,
        ]);
    }

    // Cek status laporan
    public function getStatus($nomor_tiket)
    {
        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json(['error' => 'Laporan tidak ditemukan'], 404);
        }

        return response()->json([
            'nomor_tiket' => $laporan->nomor_tiket,
            'status' => $laporan->status,
            'jenis_laporan' => $laporan->jenis_laporan,
            'judul' => $laporan->judul,
        ]);
    }

    // Update status laporan
    public function updateStatus(Request $request, $nomor_tiket)
    {
        $request->validate(['status' => 'required|string']);

        $laporan = Laporan::where('nomor_tiket', $nomor_tiket)->first();

        if (!$laporan) {
            return response()->json(['error' => 'Laporan tidak ditemukan'], 404);
        }

        $laporan->update(['status' => $request->status]);

        return response()->json(['success' => true, 'status' => $laporan->status]);
    }
}
