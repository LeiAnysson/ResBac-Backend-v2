<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
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
    public function uploadAnnouncementImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', 
        ]);

        $path = $request->file('image')->store('public/announcement'); 
        $fileName = $request->file('image')->hashName();

        $userId = Auth::id();
        $image = Image::create([
            'file_name' => $fileName,
            'file_path' => '/storage/announcement/' . $fileName, 
            'uploaded_by' => $userId,
        ]);

        return response()->json($image);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif|max:10240', 
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'posted_at' => now(),
            'uploaded_by' => Auth::id(), 
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('announcement', 'public'); 

                $image = Image::create([
                    'file_path' => "/storage/$path",
                    'description' => null,
                ]);

                $announcement->images()->attach($image->id);
            }
        }

        return response()->json([
            'message' => 'Announcement created successfully',
            'announcement' => $announcement->load('images', 'poster')
        ], 201);
    }
}