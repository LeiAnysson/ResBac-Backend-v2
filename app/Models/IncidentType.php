<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\IncidentPriority;
use App\Models\IncidentReport;

class IncidentType extends Model
{
    use SoftDeletes;

    protected $table = 'incident_types';
    protected $primaryKey = 'id'; 
    public $timestamps = true;

    protected $fillable = [
        'name',
        'priority_id',
        'icon'
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
