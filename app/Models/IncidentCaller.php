<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentCaller extends Model
{   
    protected $fillable = [
        'incident_id', 'name', 'phone'
    ];

    public function incident()
    {
        return $this->belongsTo(IncidentReport::class);
    }
        
}
