<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelName;
    public $message;

    public function __construct($channelName, $message)
    {
        $this->channelName = $channelName;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel($this->channelName);
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'timestamp' => now()->toDateTimeString(),
        ];
    }
}
