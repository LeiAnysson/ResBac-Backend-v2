<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResidentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'id_number',
        'id_image_path',
        'full_name',
        'address',
        'birthdate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
