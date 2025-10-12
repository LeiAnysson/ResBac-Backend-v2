<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'age',
        'birthdate',
        'address',
        'contact_num',
        'role_id',
        'created_at',
        'residency_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'updated_at'
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function residentProfile()
    {
        return $this->hasOne(ResidentProfile::class);
    }

    public function responseTeamMember()
    {
        return $this->hasOne(ResponseTeamMember::class, 'user_id', 'id');
    }

}
