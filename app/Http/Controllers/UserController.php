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

        if ($request->has('search') && $request->search !== "") {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(10));
    }

    public function totalUsers()
    {
        $count = User::count();

        return response()->json(['total_users' => $count]);
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

    public function createAccount(Request $request)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users',
            'password'     => 'required|string|min:6',
            'age'          => 'required|integer|min:18',
            'birthdate'    => 'required|date',
            'address'      => 'required|string|max:255',
            'contact_num'  => 'required|string|max:15',
            'role'         => 'required|in:Resident,Responder,MDRRMO',
            'team_id'      => 'nullable|exists:response_teams,id'
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();

        if ($validated['role'] === 'Responder' && empty($validated['team_id'])) {
            return response()->json([
                'message' => 'team_id is required for Responders.'
            ], 422);
        }

        $residencyStatus = match ($validated['role']) {
            'Resident'  => 'pending',
            'Responder', 'MDRRMO' => 'N/A',
            default     => 'N/A'
        };

        $user = User::create([
            'first_name'       => $validated['first_name'],
            'last_name'        => $validated['last_name'],
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'age'              => $validated['age'],
            'birthdate'        => $validated['birthdate'],
            'address'          => $validated['address'],
            'contact_num'      => $validated['contact_num'],
            'role_id'          => $role->id,
            'residency_status' => $residencyStatus,
        ]);

        if ($validated['role'] === 'Responder') {
            ResponseTeamMember::create([
                'team_id' => $validated['team_id'],
                'user_id' => $user->id,
            ]);
        }

        return response()->json([
            'message' => $validated['role'] . ' account created successfully',
            'user'    => $user
        ], 201);
    }

    // dashboard data  (still not working T.T)
    // public function pendingResidents()
    // {
    //     $pending = User::whereHas('role', fn($q) => $q->where('name', 'resident'))
    //                 ->where('residency_status', 'pending')->get();
    //     return response()->json($pending);
    // }
}
