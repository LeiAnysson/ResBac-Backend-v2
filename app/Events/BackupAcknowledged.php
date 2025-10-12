<?php

namespace App\Events;

use App\Models\IncidentReport;
use App\Models\BackupRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class BackupAcknowledged implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $incident;
    public $backup;

    public function __construct(IncidentReport $incident, BackupRequest $backup)
    {
        $this->incident = $incident;
        $this->backup = $backup;
    }

    public function broadcastOn()
    {
        return [ new Channel('responder') ];
    }

    public function broadcastAs()
    {
        return 'BackupAcknowledged';
    }

    public function broadcastWith()
    {
        return [
            'incident' => [
                'id' => $this->incident->id,
                'status' => $this->incident->status,
            ],
            'backup_type' => $this->backup->backup_type,
            'team' => [
                'id' => $this->backup->response_team_id,
            ],
        ];
    }
}
