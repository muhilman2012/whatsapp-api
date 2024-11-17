<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_tiket',
        'namalengkap',
        'nik',
        'jenis_kelamin',
        'alamatlengkap',
        'jenis_laporan',
        'judul',
        'detail',
        'lokasi',
        'dokumenpendukung',
        'tanggalkejadian',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate nomor tiket unik 5 digit
        static::creating(function ($laporan) {
            $laporan->nomor_tiket = strtoupper(substr(md5(uniqid()), 0, 5));
        });
    }
}
