<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CallAlreadyAccepted implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $callId;

    public function __construct($callId)
    {
        $this->callId = $callId;
    }

    public function broadcastOn()
    {
        return new Channel('dispatcher');
    }

    public function broadcastAs()
    {
        return 'CallAlreadyAccepted';
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->callId,
        ];
    }
}
