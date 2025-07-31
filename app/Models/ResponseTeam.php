<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseTeam extends Model
{
    protected $fillable = ['team_name', 'status'];

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
}
