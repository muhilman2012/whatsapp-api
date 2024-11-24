<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_tiket',
        'nomor_pengadu',
        'email',
        'nama_lengkap',
        'nik',
        'jenis_kelamin',
        'alamat_lengkap',
        'judul',
        'detail',
        'lokasi',
        'dokumen_pendukung',
        'tanggal_kejadian',
        'status',
        'tanggapan',
        'klasifikasi', // Field baru
        'kategori',    // Field baru
        'disposisi',   // Field baru
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
    ];

    protected $attributes = [
        'tanggapan' => 'Laporan pengaduan Anda dalam proses verifikasi & penelaahan, sesuai ketentuan akan dilakukan dalam 14 (empat belas) hari kerja sejak laporan lengkap diterima.',
    ];

    /**
     * Accessor untuk format tanggal DD/MM/YYYY.
     */
    public function getTanggalKejadianAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : null;
    }

    /**
     * Mutator untuk format tanggal ke YYYY-MM-DD.
     */
    public function setTanggalKejadianAttribute($value)
    {
        $this->attributes['tanggal_kejadian'] = $value ? \Carbon\Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d') : null;
    }

    /**
     * Mutator untuk jenis_kelamin: Simpan L/P berdasarkan input Laki-laki/Perempuan.
     */
    public function setJenisKelaminAttribute($value)
    {
        $this->attributes['jenis_kelamin'] = $value === 'Laki-laki' ? 'L' : ($value === 'Perempuan' ? 'P' : null);
    }

    /**
     * Accessor untuk jenis_kelamin: Kembalikan Laki-laki/Perempuan.
     */
    public function getJenisKelaminAttribute($value)
    {
        return $value === 'L' ? 'Laki-laki' : ($value === 'P' ? 'Perempuan' : null);
    }
}