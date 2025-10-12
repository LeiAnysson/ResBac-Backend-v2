<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident_id;
    public $responseTeamId;
    public $backup_type;

    public function __construct($incident_id, $responseTeamId, $backup_type)
    {
        $this->incident_id = $incident_id;
        $this->responseTeamId = $responseTeamId;
        $this->backup_type = $backup_type;
    }

    public function broadcastOn()
    {
        return new Channel('dispatcher');
    }

    public function broadcastAs()
    {
        return 'BackupRequestCreated';
    }
}
