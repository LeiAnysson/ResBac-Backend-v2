<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $payload;

    public function __construct($incidentId, $endedByRole, $endedById = null, $reporterId = null)
    {
        $this->payload = [
            'incidentId'   => $incidentId,
            'endedByRole'  => $endedByRole,
            'endedById'    => $endedById,
            'reporterId'   => $reporterId, 
        ];
    }

    public function broadcastWith()
    {
        return $this->payload;
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
        return 'CallEnded';
    }
}
