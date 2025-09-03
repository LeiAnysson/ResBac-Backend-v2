<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodeController extends Controller
{
    public function geocode(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
        ]);

        $apiKey = env('HERE_API_KEY'); 
        $url = "https://geocode.search.hereapi.com/v1/geocode";

        $response = Http::get($url, [
            'q' => $request->location,
            'apiKey' => $apiKey,
        ]);

        if ($response->failed() || empty($response['items'])) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $position = $response['items'][0]['position'];

        return response()->json([
            'latitude' => $position['lat'],
            'longitude' => $position['lng'],
        ]);
    }
}
