<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentType;
use App\Models\IncidentPriority;

class IncidentTypeController extends Controller
{
    public function index()
    {
        $incidentTypes = IncidentType::with('priority')->paginate(5);

        return response()->json($incidentTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'priority_id' => 'required|exists:incident_priorities,id',
        ]);

        $incidentType = IncidentType::create($validated);

        return response()->json([
            'message' => 'Incident type created successfully!',
            'data' => $incidentType->load('priority'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $incidentType = IncidentType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'priority_id' => 'required|exists:incident_priorities,id',
        ]);

        $incidentType->update($validated);

        return response()->json([
            'message' => 'Incident type updated successfully!',
            'data' => $incidentType->load('priority'),
        ]);
    }

    public function destroy($id)
    {
        $incidentType = IncidentType::findOrFail($id);
        $incidentType->delete(); 

        return response()->json([
            'message' => 'Incident type deleted successfully!',
        ]);
    }

    public function allIncidentTypes()
    {
        $incidentTypes = IncidentType::with('priority')->get(); 
        return response()->json($incidentTypes);
    }

    public function priorities()
    {
        $priorities = IncidentPriority::all(); 
        return response()->json($priorities);
    }

}
