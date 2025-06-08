<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    use HasFactory;

    protected $fillable = ['laporan_id', 'file_name', 'file_path'];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}
