<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Identitas;
use Illuminate\Support\Facades\Storage;

class IdentitasController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'nik' => 'required|digits:16|unique:identitas,nik',
                'jenis_kelamin' => 'required|in:L,P',
                'email' => 'nullable|email|max:255',
                'nomor_pengadu' => 'nullable|string|max:20',
                'alamat_lengkap' => 'required|string|max:1000',
                'foto_ktp' => 'required|string', // base64
            ]);

            // Simpan foto KTP dari base64
            $fotoBase64 = $request->foto_ktp;
            $fotoName = 'ktp_' . $request->nik . '.png';
            $path = 'identitas/' . $fotoName;
            Storage::disk('public')->put($path, base64_decode($fotoBase64));

            // URL publik ke file
            $url = asset('storage/' . $path);

            // Simpan ke database
            $identitas = Identitas::create([
                'nama_lengkap' => $request->nama_lengkap,
                'nik' => $request->nik,
                'jenis_kelamin' => $request->jenis_kelamin,
                'email' => $request->email,
                'nomor_pengadu' => $request->nomor_pengadu,
                'alamat_lengkap' => $request->alamat_lengkap,
                'foto_ktp' => $path,
                'foto_ktp_url' => $url,
                'is_filled' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data identitas berhasil disimpan.',
                'data' => $identitas
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 200);
        }
    }
}
