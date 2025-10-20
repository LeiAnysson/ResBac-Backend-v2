<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResidencyApprovedMail;
use App\Mail\ResidencyRejectedMail;
use App\Models\ResidentProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminResidentController extends Controller
{
    public function index(Request $request)
    {   
        $query = ResidentProfile::query();

        if ($request->has('residency_status')) {
            $query->where('residency_status', $request->residency_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }

        $residents = $query->with('user')->paginate(10);

        return response()->json($residents);
    }

    public function pendingResidentsCount()
    {
        $count = User::where('residency_status', 'pending')->count();
    
        Log::info('pendingResidentsCount hit');
    
        return response()->json([
            'pending_residents' => $count
        ]);
    }

    public function show($id)
    {
        $user = User::with('residentProfile')->find($id);
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $response = [
            'id'          => $user->id,
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'email'       => $user->email,
            'birthdate'   => $user->birthdate,
            'address'     => $user->address,
            'contact_num' => $user->contact_num,
            'role'        => $user->role->name ?? null,
            'role_id'     => $user->role_id,
        ];
    
        if ($user->role_id == 4) { 
            $response['status'] = $user->residency_status;
            $response['resident_profile'] = [
                'id_image_path' => $user->residentProfile->id_image_path ?? null,
                'id_number'     => $user->residentProfile->id_number ?? null,
            ];
        } else {
            $response['team_id'] = $user->team_id ?? null;
        }
    
        return response()->json($response);
    }

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $user->residency_status = 'approved';
            $user->save();

            recordActivity('approved residency request', 'User', $user->id);

            Mail::to($user->email)->send(new ResidencyApprovedMail($user));

            DB::commit();

            return response()->json([
                'message' => 'User approved successfully.',
                'email_sent_to' => $user->email,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Approval failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $user->residency_status = 'rejected';
            $user->save();

            recordActivity('rejected residency request', 'User', $user->id);

            Mail::to($user->email)->send(new ResidencyRejectedMail($user));

            DB::commit();

            return response()->json([
                'message' => 'User rejected successfully.',
                'email_sent_to' => $user->email,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Rejection failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
