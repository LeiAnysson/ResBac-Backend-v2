<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'updated_by',
        'update_details',
    ];

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
