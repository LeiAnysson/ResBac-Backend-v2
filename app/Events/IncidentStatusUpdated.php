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
    public $target_roles;

    /**
     * @param IncidentReport $incident
     * @param array|null $target_roles 
     */
    public function __construct(IncidentReport $incident, ?array $target_roles = null)
    {
        $this->incident = $incident;
        $this->target_roles = $target_roles ?? [2, 4]; 
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
            'incident' => $this->incident->toArray(),
            'target_roles' => $this->target_roles,
        ];
    }
}
