<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'content', 'posted_by', 'posted_at'];

    public function images()
    {
        return $this->belongsToMany(Image::class, 'announcement_images', 'announcement_id', 'image_id');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
