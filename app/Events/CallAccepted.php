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

    public function broadcastWith()
    {
        return [
            'id' => $this->report->id,
            'incident_type' => [
                'id' => $this->report->incidentType->id,
                'name' => $this->report->incidentType->name,
            ],
            'status' => $this->report->status,
            'user' => [
                'id' => $this->report->user->id,
                'first_name' => $this->report->user->first_name,
                'last_name' => $this->report->user->last_name,
            ],
        ];
    }

}
