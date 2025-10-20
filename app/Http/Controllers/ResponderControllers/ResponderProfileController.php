<?php

namespace App\Http\Controllers\ResponderControllers; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\User; 
use Illuminate\Support\Facades\Auth;

class ResponderProfileController extends Controller
{
    public function show($id)
    {
        $responder = User::with(['role', 'responseTeamMember.team'])
            ->where('id', $id)
            ->where('role_id', 3)
            ->with('userImage.image')
            ->first();

        if (!$responder) {
            return response()->json([
                'message' => 'Responder not found',
            ], 404);
        }

        return response()->json([
            'id' => $responder->id,
            'first_name' => $responder->first_name,
            'last_name' => $responder->last_name,
            'email' => $responder->email,
            'address' => $responder->address,
            'contact_num' => $responder->contact_num,
            'role_name' => $responder->role->name ?? null,
            'age' => $responder->age,
            'birthdate' => $responder->birthdate,
            'created_at' => $responder->created_at,
            'role' => $responder->role ? $responder->role->role_name : null,
            'team' => $responder->responseTeamMember && $responder->responseTeamMember->team
                      ? $responder->responseTeamMember->team->team_name
                      : null,
            'profile_image_url' => $responder->userImage && $responder->userImage->image
                ? $responder->userImage->image->file_path
                : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $responder = User::where('id', $id)
            ->where('role_id', 3)
            ->first();

        if (!$responder) {
            return response()->json(['message' => 'Responder not found'], 404);
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

        $responder->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $responder
        ]);
    }
}