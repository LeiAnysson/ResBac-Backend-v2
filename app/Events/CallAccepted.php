<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\IncidentReport;

class CallAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $report;

    public function __construct(IncidentReport $report)
    {
        $this->report = $report;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('resident.' . $this->report->reported_by);
    }


    public function broadcastAs()
    {
        return 'CallAccepted';
    }
}
