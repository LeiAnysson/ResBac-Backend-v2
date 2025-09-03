<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseTeamAssignment extends Model
{
    protected $table = 'response_team_assignments';
    protected $primaryKey = 'rt_assignment_id';

    protected $fillable = [
        'incident_id',
        'dispatcher_id',
        'team_id',
        'status',
        'assigned_at',
    ];

    public function incident()
    {
        return $this->belongsTo(IncidentReport::class, 'incident_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function team()
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }
}
