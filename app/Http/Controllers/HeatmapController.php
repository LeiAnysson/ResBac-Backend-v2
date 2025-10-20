<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\IncidentReport;
use Carbon\Carbon;

class HeatmapController extends Controller
{
    public function incidentsByBarangay()
    {
        $barangayCenters = [
            'Antipona' => ['lat' => 14.796710302625746, 'lng' => 120.92571517084788],
            'Bagumbayan' => ['lat' => 14.798755207045811, 'lng' => 120.92223659513795],
            'Bambang' => ['lat' => 14.792081479953506, 'lng' => 120.92703003011664],
            'Batia' => ['lat' => 14.830494900053656, 'lng' => 120.94440360863192],
            'Biñang 1st' => ['lat' => 14.794465475733112, 'lng' => 120.93071966630224],
            'Biñang 2nd' => ['lat' => 14.796226366453165, 'lng' => 120.92977125280929],
            'Bolacan' => ['lat' => 14.801054497380958, 'lng' => 120.94534804479464],
            'Bundukan' => ['lat' => 14.788855695696386, 'lng' => 120.94242980150366],
            'Bunlo' => ['lat' => 14.785065605119241, 'lng' => 120.93242833746642],
            'Caingin' => ['lat' => 14.801202492340046, 'lng' => 120.9250588877892],
            'Duhat' => ['lat' => 14.785801481791577, 'lng' => 120.95299469034421],
            'Igulot' => ['lat' => 14.79086400567233, 'lng' => 120.93822275280898],
            'Lolomboy' => ['lat' => 14.781194792608485, 'lng' => 120.93334959513754],
            'Poblacion' => ['lat' => 14.795192470380112, 'lng' => 120.9254473572382],
            'Sulucan' => ['lat' => 14.791370931924945, 'lng' => 120.92572249513785],
            'Taal' => ['lat' => 14.812863850402415, 'lng' => 120.93281586089572],
            'Tambobong' => ['lat' => 14.816177172351287, 'lng' => 120.93928366260407],
            'Turo' => ['lat' => 14.807105724002051, 'lng' => 120.93843247027503],
            'Wakas' => ['lat' => 14.805380188970249, 'lng' => 120.919282434933],
        ];

        $now = Carbon::now();
        $incidents = IncidentReport::select('id', 'latitude', 'longitude', 'created_at')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->get();
        $barangayCounts = [];

        foreach ($incidents as $incident) {
            $cacheKey = "barangay_{$incident->latitude}_{$incident->longitude}";

            $barangay = Cache::remember($cacheKey, now()->addDays(30), function () use ($incident) {
                $apiKey = env('HERE_API_KEY');
                $url = "https://revgeocode.search.hereapi.com/v1/revgeocode?at={$incident->latitude},{$incident->longitude}&apikey={$apiKey}";
                $response = Http::get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['items'][0]['address']['district'] ?? 'Unknown';
                }

                return 'Unknown';
            });

            if (!isset($barangayCounts[$barangay])) {
                $barangayCounts[$barangay] = 0;
            }
            $barangayCounts[$barangay]++;
        }

        $results = collect($barangayCenters)->map(function ($coords, $barangay) use ($barangayCounts) {
            return [
                'barangay' => $barangay,
                'count' => $barangayCounts[$barangay] ?? 0,
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
            ];
        })->values();

        return response()->json($results);
    }
}
