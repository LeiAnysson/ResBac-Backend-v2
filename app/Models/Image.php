<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = ['file_name', 'file_path', 'uploaded_by'];

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_images', 'image_id', 'announcement_id');
    }
}
