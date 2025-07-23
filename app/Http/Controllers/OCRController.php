<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OCRController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'id_image' => 'required|image|mimes:jpg,jpeg,png|max:20480',
        ]);

        $path = $request->file('id_image')->store('public/ocr_uploads');
        $fileName = str_replace('public/', 'storage/', $path);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'id_image_path' => $fileName
        ]);
    }
}
