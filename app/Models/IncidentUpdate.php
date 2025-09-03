<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentUpdate extends Model
{
    public function incident()
    {
        return $this->belongsTo(IncidentReport::class);
    }

}
