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

    public function pendingResidentsCount(Request $request)
    {
        $count = User::where('role_id', 4)->where('residency_status', 'pending')->count();
        Log::info('pendingResidentsCount hit');
        return response()->json([
            'pending_residents' => $count
        ]);
    }

    public function show($id)
    {
        $resident = User::with('residentProfile')
            ->where('role_id', 4)->where('id', $id)->first();
        
        if (!$resident){
            return response()->json(['message' => 'Resident not found'], 404);
        }

        return response()->json([
            'id'        => $resident->id,
            'name'      => trim($resident->first_name . ' ' . $resident->last_name),
            'email'     => $resident->email,
            'birthdate' => $resident->birthdate,
            'address'   => $resident->address,
            'status'    => $resident->residency_status,
            'resident_profile' => [
                'id_image_path' => $resident->residentProfile->id_image_path ?? null,
                'id_number'     => $resident->residentProfile->id_number ?? null,
            ]
        ]);
    }

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);
            $user->residency_status = 'approved';
            $user->save();

            recordActivity('approved residency request', 'User', $user->id);

            // Mail::to($user->email)->send(new ResidencyApprovedMail($user));

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

            // Mail::to($user->email)->send(new ResidencyRejectedMail($user));

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
