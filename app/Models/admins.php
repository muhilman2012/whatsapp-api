<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class admins extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'admins';

    protected $primaryKey = 'id_admins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'nama',
        'email',
        'password',
        'phone',
        'born',
        'country',
        'avatar',
        'address',
        'role',
        'jabatan',
        'deputi',
        'unit',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function laporans()
    {
        return $this->belongsToMany(Laporan::class, 'assignments', 'assigned_to', 'laporan_id');
    }

    public function assignedAssignments()  
    {  
        return $this->hasMany(Assignment::class, 'assigned_by');  
    }  
  
    public function notifications()  
    {  
        return $this->hasMany(Notification::class, 'assignee_id');  
    }
    
    public function hasRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
