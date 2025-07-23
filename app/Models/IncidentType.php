<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $table = 'incident_types';

    protected $primaryKey = 'id'; 

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class, 'incident_type_id');
    }
}
