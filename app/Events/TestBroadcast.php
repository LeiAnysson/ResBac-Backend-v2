<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestBroadcast implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $message;
    public $residentId;

    public function __construct($message, $residentId = null)
    {
        $this->message = $message;
        $this->residentId = $residentId;
    }

    public function broadcastOn()
    {
        $channels = [new PrivateChannel('dispatcher-channel')];

        if ($this->residentId) {
            $channels[] = new PrivateChannel('resident.' . $this->residentId);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'TestBroadcast';
    }
}
