<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\IncidentType;

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
        'location',
        'description',
        'status',
        'reported_at',
    ];

    protected $dates = ['reported_at'];

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

}
