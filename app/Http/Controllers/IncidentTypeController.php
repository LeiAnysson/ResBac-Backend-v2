<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncidentType;

class IncidentTypeController extends Controller
{
    public function index()
    {
        $incidentTypes = IncidentType::with('priority')->paginate(5);

        return response()->json($incidentTypes);
    }

    public function update(Request $request, $id)
    {
        $incidentType = IncidentType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'priority_id' => 'required|exists:incident_priorities,id',
        ]);

        $incidentType->update([
            'name' => $request->name,
            'priority_id' => $request->priority_id,
        ]);

        return response()->json([
            'message' => 'Incident type updated successfully!',
            'data' => $incidentType->load('priority'),
        ]);
    }

}
