<?php

namespace App\Events;

use App\Models\IncidentReport;
use App\Models\BackupRequest;
use App\Models\ResponseTeam;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class BackupAutomaticallyAssigned implements ShouldBroadcastNow
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
        return [
            new Channel('responder'),
            new Channel('dispatcher'),
        ];
    }

    public function broadcastAs()
    {
        return 'BackupAutomaticallyAssigned';
    }

    public function broadcastWith()
    {
        return [
            'type' => 'medic_backup_auto_assigned',
            'incident' => [
                'id' => $this->incident->id,
                'status' => $this->incident->status,
                'location' => $this->incident->landmark ?? "{$this->incident->latitude}, {$this->incident->longitude}",
                'incident_type' => $this->incident->incidentType->name ?? 'Unknown',
            ],
            'backup' => [
                'id' => $this->backup->id,
                'type' => $this->backup->backup_type,
                'reason' => $this->backup->reason,
                'status' => $this->backup->status,
                'requested_at' => $this->backup->requested_at,
            ],
            'assigned_team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ],
        ];
    }
}
