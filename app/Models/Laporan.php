<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $table = 'laporans';

    protected $fillable = [
        'nomor_tiket',
        'nomor_pengadu',
        'email',
        'nama_lengkap',
        'nik',
        'jenis_kelamin',
        'alamat_lengkap',
        'jenis_laporan',
        'judul',
        'detail',
        'lokasi',
        'dokumen_pendukung',
        'tanggal_kejadian',
        'status',
        'tanggapan',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'Diproses',
    ];
}