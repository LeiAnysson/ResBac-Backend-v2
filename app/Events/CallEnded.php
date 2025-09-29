<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEnded implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $endedBy;
    public $incidentId;

    public function __construct($incidentId, $endedBy)
    {
        $this->incidentId = $incidentId;
        $this->endedBy = $endedBy;
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
