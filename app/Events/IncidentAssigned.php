<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAssigned implements ShouldBroadcastNow
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

    public function broadcastWith()
    {
        $team = is_array($this->team)
            ? (object) $this->team
            : $this->team;

        return [
            'incident' => [
                'id' => $this->incident->id,
                'description' => $this->incident->description,
                'latitude' => $this->incident->latitude,
                'longitude' => $this->incident->longitude,
                'landmark' => $this->incident->landmark,
                'status' => $this->incident->status,
                'incident_type' => [
                    'id' => $this->incident->incidentType->id ?? null,
                    'name' => $this->incident->incidentType->name ?? 'Unknown',
                ],
                'caller_name' => $this->incident->caller_name,
            ],
            'target_role' => 3,
            'team_id' => $team->id ?? null,
            'team_name' => $team->team_name ?? null,
        ];
    }
}
