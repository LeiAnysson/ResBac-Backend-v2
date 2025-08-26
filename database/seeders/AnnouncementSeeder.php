<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\Image;
use Illuminate\Support\Facades\DB;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); 
        DB::table('announcement_images')->truncate();
        DB::table('images')->truncate();
        DB::table('announcements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 

        $ann1 = Announcement::create([
            'title' => 'Typhoon Preparedness Advisory',
            'content' => 'With the upcoming rainy season, please ensure your emergency kits are ready. Secure loose items around your property and stay updated with official weather bulletins from PAGASA. Visit the MDRRMO website for more tips.',
            'posted_by' => 1, 
            'posted_at' => now(),
        ]);

        $ann2 = Announcement::create([
            'title' => 'Safety Advisory: What To Do If Your Clothes Catch Fire',
            'content' => 'Knowing what to do if your clothes catch fire can save your life:
                - STOP: Stop immediately where you are. Do not run.
                - DROP: Drop to the ground as quickly as possible.
                - ROLL: Cover your face with your hands and roll over and over to smother the flames.
                Stay calm and act fast. Share this important safety information with your family and friends.',
            'posted_by' => 1,
            'posted_at' => now(),
        ]);

        // Images
        $img1 = Image::create([
            'file_name' => 'typhoon.png',
            'file_path' => '/images/typhoon.png',
            'uploaded_by' => 1, 
        ]);

        $img2 = Image::create([
            'file_name' => 'fire-safety.png',
            'file_path' => '/images/fire-safety.png',
            'uploaded_by' => 1, 
        ]);

        $ann1->images()->attach($img1->id);
        $ann2->images()->attach($img2->id);
    }
}
