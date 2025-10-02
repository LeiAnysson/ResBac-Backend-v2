<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class DuplicateReportCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $duplicate;

    public function __construct($duplicate)
    {
        $this->duplicate = $duplicate;
    }

    public function broadcastOn()
    {
        return new Channel('dispatcher');
    }

    public function broadcastAs()
    {
        return 'duplicateReportCreated';
    }
}
