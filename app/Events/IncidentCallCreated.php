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

    public function broadcastWith()
    {
        return [
            'id' => $this->report->id,
            'incident_type' => [
                'id' => $this->report->incidentType->id,
                'name' => $this->report->incidentType->name,
            ],
            'user' => [
                'id' => $this->report->user->id,
                'first_name' => $this->report->user->first_name,
                'last_name' => $this->report->user->last_name,
            ],
            'status' => $this->report->status,
            'latitude' => $this->report->latitude,
            'longitude' => $this->report->longitude,
            'landmark' => $this->report->landmark,
            'description' => $this->report->description,
        ];
    }

}
