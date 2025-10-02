<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $incident;
    public $team;

    public function __construct($incident, $team)
    {
        $this->incident = $incident;
        $this->team = $team;
    }

    public function broadcastOn()
    {
        return new Channel('responder');
    }

    public function broadcastAs()
    {
        return 'IncidentAssigned';
    }
}
