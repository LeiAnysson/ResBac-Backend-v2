<?php

namespace App\Events;

use App\Models\IncidentReport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class IncidentUpdated implements ShouldBroadcastNow
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
            new Channel('responder'),
            new Channel('resident'),
        ];
    }

    public function broadcastAs()
    {
        return 'IncidentUpdated';
    }
}
