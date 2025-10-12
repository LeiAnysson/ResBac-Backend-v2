<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupRequest extends Model
{
    use SoftDeletes;

    protected $table = 'backup_requests';
    protected $fillable = ['response_team_id', 'incident_id', 'status', 'backup_type', 'requested_at', 'reason'];

    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    public function incident()
    {
        return $this->belongsTo(IncidentReport::class, 'incident_id');
    }
}
