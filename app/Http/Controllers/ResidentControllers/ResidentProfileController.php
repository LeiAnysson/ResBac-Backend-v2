<?php

namespace App\Http\Controllers\ResidentControllers; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\User; 
use Illuminate\Support\Facades\Auth;

class ResidentProfileController extends Controller
{
    public function show($id)
    {
        $resident = User::where('id', $id)
            ->where('role_id', 4) 
            ->first();

        if (!$resident) {
            return response()->json([
                'message' => 'Resident not found',
            ], 404);
        }

        return response()->json([
            'id' => $resident->id,
            'first_name' => $resident->first_name,
            'last_name' => $resident->last_name,
            'email' => $resident->email,
            'address' => $resident->address,
            'contact_num' => $resident->contact_num,
            'age' => $resident->age,
            'birthdate' => $resident->birthdate,
            'residency_status' => $resident->residency_status,
            'created_at' => $resident->created_at,
        ]);
    }

    public function update(Request $request, $id)
    {
        $resident = User::where('id', $id)->where('role_id', 4)->first();

        if (!$resident) {
            return response()->json(['message' => 'Resident not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'address' => 'string|nullable',
            'email' => 'email',
            'contact_num' => 'string|nullable',
            'age' => 'integer|nullable',
            'birthdate' => 'date|nullable',
        ]);

        $resident->update($validated);

        return response()->json(['message' => 'Profile updated successfully', 'data' => $resident]);
    }

}