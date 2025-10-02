<?php

namespace App\Helpers;

use App\Models\IncidentReport;

class IncidentHelper
{
    public static function checkDuplicateReport($incidentTypeId, $latitude, $longitude, $radius = 50)
    {
        $incidents = IncidentReport::where('incident_type_id', $incidentTypeId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($incidents as $incident) {
            $distance = self::distanceInMeters($latitude, $longitude, $incident->latitude, $incident->longitude);
            if ($distance <= $radius) {
                return $incident;
            }
        }

        return null;
    }

    public static function addDuplicateReporter($incident, $userId)
    {
        $duplicates = $incident->duplicates 
            ? json_decode($incident->duplicates, true) 
            : [];

        if (!collect($duplicates)->pluck('user_id')->contains($userId)) {
            $duplicates[] = [
                'user_id' => $userId,
                'reported_at' => now()->toDateTimeString(),
            ];
        }

        $incident->duplicates = json_encode($duplicates);
        $incident->save();
    }

    public static function distanceInMeters($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
