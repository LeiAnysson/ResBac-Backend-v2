<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\IncidentPriority;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentType extends Model
{
    use SoftDeletes;
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
    public function priority()
    {
        return $this->belongsTo(IncidentPriority::class, 'priority_id');
    }

}
