<?php

namespace App\Http\Controllers\ResidentControllers; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\Announcement;

class ResidentAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['images', 'poster'])
            ->orderBy('posted_at', 'desc')
            ->get()
            ->map(function($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'content' => $a->content,
                    'posted_at' => $a->posted_at,
                    'images' => $a->images->map(fn($img) => [
                        'id' => $img->id,
                        'file_path' => $img->file_path
                    ])->toArray(),
                    'poster' => $a->poster
                        ? [
                            'id' => $a->poster->id ?? null,
                            'name' => $a->poster->name ?? 'Unknown',
                        ]
                        : [
                            'id' => null,
                            'name' => 'Unknown',
                        ],
                ];
            });

        return response()->json($announcements);
    }
}