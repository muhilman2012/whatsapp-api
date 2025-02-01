<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignee_id',
        'assigner_id',
        'laporan_id',
        'is_read',
        'message',
        'role',
    ];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    public function assigner()
    {
        return $this->belongsTo(admins::class, 'assigner_id');
    }
}
