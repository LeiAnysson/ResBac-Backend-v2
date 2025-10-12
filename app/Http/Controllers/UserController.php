<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Models\ResponseTeamMember;
use App\Models\ResidentProfile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
        
        if ($request->has('no_team') && $request->no_team) {
            $query->whereDoesntHave('responseTeamMember');
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
            'age'          => 'nullable|integer|min:18',
            'birthdate'    => 'nullable|date',
            'address'      => 'required|string|max:255',
            'contact_num'  => 'required|string|max:15',
            'role'         => 'required|in:Resident,Responder,MDRRMO',
            'team_id'      => 'nullable|exists:response_teams,id',
            'id_image'     => 'required_if:role,Resident|image|mimes:jpg,jpeg,png|max:10240',
            'id_number'    => 'nullable|string|max:255',
        ], [
            'id_image.required_if' => 'A valid ID is required for Residents.',
        ]);

        $role = Role::where('name', $validated['role'])->firstOrFail();

        $idImagePath = null;
        if ($request->hasFile('id_image')) {
            $file = $request->file('id_image');
            $idImagePath = $file->storeAs(
                'id_images',
                $file->hashName(),
                'public'
            );
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
            'birthdate'        => $validated['birthdate'] ?? null,
            'address'          => $validated['address'],
            'contact_num'      => $validated['contact_num'],
            'role_id'          => $role->id,
            'residency_status' => $residencyStatus,
        ]);

        if ($validated['role'] === 'Resident') {
            ResidentProfile::create([
                'user_id'       => $user->id,
                'id_image_path' => $idImagePath,
                'id_number'     => $validated['id_number'] ?? '',
                'full_name'     => $validated['first_name'] . ' ' . $validated['last_name'],
                'address'       => $validated['address'],
                'birthdate'     => $validated['birthdate'] ?? null,
            ]);
        }
        
        if ($role->name === 'Responder' && !empty($validated['team_id'])) {
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

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name'   => 'sometimes|required|string|max:255',
            'last_name'    => 'sometimes|required|string|max:255',
            'email'        => 'sometimes|required|email|unique:users,email,' . $user->id,
            'age'          => 'sometimes|nullable|integer|min:18',
            'birthdate'    => 'sometimes|nullable|date',
            'address'      => 'sometimes|required|string|max:255',
            'contact_num'  => 'sometimes|required|string|max:15',
            'role'         => 'sometimes|required|in:Resident,Responder,MDRRMO',
            'team_id'      => 'sometimes|nullable|exists:response_teams,id',
            'id_image'     => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:10240',
            'id_number'    => 'sometimes|nullable|string|max:255',
        ]);

        if (isset($validated['role'])) {
            $role = Role::where('name', $validated['role'])->firstOrFail();

            if ($validated['role'] === 'Responder' && empty($validated['team_id'])) {
                return response()->json(['message' => 'team_id is required for Responders.'], 422);
            }

            $user->role_id = $role->id;
            $user->residency_status = match ($validated['role']) {
                'Resident' => $user->residency_status ?? 'pending',
                'Responder', 'MDRRMO' => 'N/A',
                default => $user->residency_status,
            };
        }

        $idImagePath = null;
        if ($request->hasFile('id_image')) {
            $file = $request->file('id_image');
            $idImagePath = $file->storeAs('id_images', $file->hashName(), 'public');
        }

        $user->update([
            'first_name'   => $validated['first_name'] ?? $user->first_name,
            'last_name'    => $validated['last_name'] ?? $user->last_name,
            'email'        => $validated['email'] ?? $user->email,
            'age'          => $validated['age'] ?? $user->age,
            'birthdate'    => $validated['birthdate'] ?? $user->birthdate,
            'address'      => $validated['address'] ?? $user->address,
            'contact_num'  => $validated['contact_num'] ?? $user->contact_num,
        ]);

        $roleName = $validated['role'] ?? $user->role?->name;

        if ($roleName === 'Resident') {
            ResidentProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_number'     => $validated['id_number'] ?? $user->residentProfile->id_number ?? '',
                    'full_name'     => ($validated['first_name'] ?? $user->first_name) . ' ' . ($validated['last_name'] ?? $user->last_name),
                    'address'       => $validated['address'] ?? $user->address,
                    'birthdate'     => $validated['birthdate'] ?? $user->birthdate,
                    'id_image_path' => $idImagePath ?? $user->residentProfile->id_image_path ?? null,
                ]
            );
        }

        if ($roleName === 'Responder' && isset($validated['team_id'])) {
            ResponseTeamMember::updateOrCreate(
                ['user_id' => $user->id],
                ['team_id' => $validated['team_id']]
            );
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }


    // dashboard data  (still not working T.T)
    // public function pendingResidents()
    // {
    //     $pending = User::whereHas('role', fn($q) => $q->where('name', 'resident'))
    //                 ->where('residency_status', 'pending')->get();
    //     return response()->json($pending);
    // }
}
