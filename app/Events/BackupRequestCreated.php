<?php

namespace App\Events;

use App\Models\IncidentReport;
use App\Models\ResponseTeam;
use App\Models\BackupRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class BackupRequestCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $incident;
    public $backup;
    public $team;

    public function __construct(IncidentReport $incident, BackupRequest $backup, ResponseTeam $team)
    {
        $this->incident = $incident;
        $this->backup = $backup;
        $this->team = $team;
    }

    public function broadcastOn()
    {
        return [ new Channel('dispatcher') ];
    }

    public function broadcastAs()
    {
        return 'BackupRequestCreated';
    }

    public function broadcastWith()
    {
        return [
            'type' => 'backup_request_created',
            'incident' => [
                'id' => $this->incident->id,
                'status' => $this->incident->status,
                'incident_type' => $this->incident->incidentType->name ?? 'Unknown',
                'location' => $this->incident->landmark ?? "{$this->incident->latitude}, {$this->incident->longitude}",
            ],
            'target_role' => 2,
            'backup' => [
                'id' => $this->backup->id,
                'backup_type' => $this->backup->backup_type,
                'reason' => $this->backup->reason,
                'status' => $this->backup->status,
                'requested_at' => $this->backup->requested_at,
            ],
            'team_id' => $this->team->id,
            'team_name' => $this->team->team_name,
        ];
    }
}
