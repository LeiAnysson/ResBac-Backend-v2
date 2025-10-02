<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseTeam extends Model
{
    protected $fillable = ['team_name', 'status', 'latitude', 'longitude'];

    public function members()
    {
        return $this->hasMany(ResponseTeamMember::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function incidentReports()
    {
        return $this->hasMany(IncidentReport::class);
    }

    public function assignments()
    {
        return $this->hasMany(ResponseTeamAssignment::class, 'team_id');
    }

    public function incidents()
    {
        return $this->hasManyThrough(
            IncidentReport::class,
            ResponseTeamAssignment::class,
            'team_id',
            'Incident_id',
            'id',
            'incident_id'
        );
    }

}
