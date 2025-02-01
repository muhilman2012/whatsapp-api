<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'laporan_id', 'activity', 'user_id'
    ];

    // Relasi ke laporan
    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }

    // Relasi ke pengguna
    public function user()
    {
        return $this->belongsTo(admins::class, 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
