<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\IncidentReport;

class IncidentCallCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $report;

    public function __construct(IncidentReport $report)
    {
        $this->report = $report->load('incidentType', 'user');
    }
    public function broadcastOn()
    {
        return new Channel('dispatcher-channel');
    }
    public function broadcastAs()
    {
        return 'IncidentCallCreated';
    }
}
