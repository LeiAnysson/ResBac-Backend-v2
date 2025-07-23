<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseTeamMember extends Model
{
    protected $table = 'response_team_members'; 

    protected $fillable = [
        'team_id',
        'user_id',
    ];

    public $timestamps = false;
    
    public function team()
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
