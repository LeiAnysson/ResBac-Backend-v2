<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPosted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function broadcastOn()
    {
        return new Channel('announcements');
    }

    public function broadcastAs()
    {
        return 'AnnouncementPosted';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'content' => $this->announcement->content,
            'posted_at' => $this->announcement->posted_at,
            'poster' => [
                'id' => $this->announcement->poster->id ?? null,
                'name' => $this->announcement->poster->name ?? 'Unknown',
            ],
            'images' => $this->announcement->images->map(fn($img) => [
                'id' => $img->id,
                'file_path' => $img->file_path,
            ]),
        ];
    }
}
