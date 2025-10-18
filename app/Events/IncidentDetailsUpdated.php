<?php

namespace App\Events;

use App\Models\IncidentReport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IncidentDetailsUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $incident;

    public function __construct(IncidentReport $incident)
    {
        $this->incident = $incident;
    }

    public function broadcastOn()
    {
        return [
            new Channel('resident'),
            new Channel('responder'),
        ];
    }

    public function broadcastAs()
    {
        return 'IncidentDetailsUpdated';
    }

    public function broadcastWith()
    {
        return [
            'incident' => is_array($this->incident) ? $this->incident : $this->incident->toArray(),
            'target_roles' => [3, 4], 
        ];
    }
}
