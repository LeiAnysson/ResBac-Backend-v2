<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\IncidentType;
use App\Models\IncidentCaller;
use App\Models\IncidentUpdate;
use App\Models\ResponseTeamAssignment;

class IncidentReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reported_by',
        'incident_type_id',
        'caller_name',
        'latitude',
        'longitude',
        'landmark',
        'description',
        'status',
        'reported_at',
        'priority_id',
        'duplicates',
    ];

    protected $dates = ['reported_at'];

    protected $casts = [
        'duplicates' => 'array',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function incidentType()
    {
        return $this->belongsTo(IncidentType::class, 'incident_type_id');
    }

    public function responseTeam()
    {
        return $this->belongsTo(ResponseTeam::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class, 'incident_id');
    }

    public function callers()
    {
        return $this->hasMany(IncidentCaller::class, 'incident_id');
    }

    public function teamAssignments()
    {
        return $this->hasMany(ResponseTeamAssignment::class, 'incident_id');
    }



}
