<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;
    
    protected $fillable = ['laporan_id', 'analis_id', 'notes', 'assigned_by'];

    public function assignedTo()
    {
        // Menghubungkan dengan tabel admins menggunakan kolom 'analis_id'
        return $this->belongsTo(admins::class, 'analis_id');
    }

    public function analis()
    {
        return $this->belongsTo(admins::class, 'analis_id');
    }

    public function assignedBy()
    {
        // Menghubungkan dengan tabel admins menggunakan kolom 'assigned_by'
        return $this->belongsTo(admins::class, 'assigned_by');
    }

    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}
