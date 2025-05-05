<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Identitas extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'nik',
        'jenis_kelamin',
        'email',
        'nomor_pengadu',
        'alamat_lengkap',
        'foto_ktp',
        'foto_ktp_url',
        'is_filled',
    ];
}
