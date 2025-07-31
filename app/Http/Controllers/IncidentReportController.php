<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentReport;

class IncidentReportController extends Controller
{
    public function index(Request $request)
    {
        $query = IncidentReport::with('incidentType');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('incident_type_id')) {
            $query->where('incident_type_id', $request->incident_type_id);
        }

        return response()->json($query->paginate(10));
    }
    public function show($id)
    {
        $incident = IncidentReport::with(['incidentType', 'user']) 
            ->where('id', $id)
            ->first();

        if (!$incident){
            return response()->json(['message' => 'Incident Report not found'], 404);
        }

        return response()->json($incident);
    }
}
