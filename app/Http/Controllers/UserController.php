<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\ResponseTeamMember;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('role')) {
            $query->where('role_id', $request->role);
        }

        if ($request->has('residency_status')) {
            $query->where('residency_status', $request->residency_status);
        }

        return response()->json($query->paginate(10));
    }
    
    public function assignRole(request $request, $id)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::findOrFail($id);
        $role = Role::where('name', $request->role)->first();

        $user->role_id = $role->id;
        $user->save(); 

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function createAccount(request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'age' => 'required|integer|min:18',
            'birthdate' => 'required|date',
            'address' => 'required|string|max:255',
            'contact_num' => 'required|string|max:15',
            'role' => 'required|in:Responder,MDRRMO',
            'team_id' => 'nullable|exists:response_teams,id'
        ]);

        $role = Role::where('name', $request->role)->first();

        if ($request->role === 'Responder' && !$request->team_id) {
            return response()->json([
                'message' => 'team_id is required for Responders.'
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'age' => $request->age,
            'birthdate' => $request->birthdate,
            'address' => $request->address,
            'contact_num' => $request->contact_num,
            'role_id' => $role->id,
        ]);

        if ($request->role === 'Responder') {
            ResponseTeamMember::create([
                'team_id' => $request->team_id,
                'user_id' => $user->id,
            ]);
        }

        return response()->json([
            'message' => $request->role . ' account created successfully',
            'user' => $user
        ]);
    }
    public function pendingResidents()
    {
        $pending = User::whereHas('role', fn($q) => $q->where('name', 'resident'))
                    ->where('residency_status', 'pending')->get();
        return response()->json($pending);
    }
}
