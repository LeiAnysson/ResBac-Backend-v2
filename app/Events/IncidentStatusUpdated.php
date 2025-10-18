<?php

namespace App\Events;

use App\Models\IncidentReport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IncidentStatusUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $incident;

    public function __construct(IncidentReport $incident, $originRole = null)
    {
        $this->incident = $incident;
    }

    public function broadcastOn()
    {
        return [
            new Channel('resident'),
            new Channel('dispatcher'),
        ];
    }

    public function broadcastAs()
    {
        return 'IncidentStatusUpdated';
    }

    public function broadcastWith()
    {
        return [
            'incident' => is_array($this->incident) ? $this->incident : $this->incident->toArray(),
            'target_roles' => [2,4]
        ];
    }
}