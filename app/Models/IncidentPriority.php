<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentPriority extends Model
{
    public function incidentTypes()
    {
        return $this->hasMany(IncidentType::class, 'priority_id');
    }
}
