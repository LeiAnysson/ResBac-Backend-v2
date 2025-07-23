<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResidencyApprovedMail;
use App\Mail\ResidencyRejectedMail;
use App\Models\ResidentProfile;
use Illuminate\Support\Facades\Auth;

class AdminResidentController extends Controller
{
    public function index(Request $request)
    {   
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden: Admins only'], 403);
        }

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

    public function show($id)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden: Admins only'], 403);
        }

        $resident = User::with('residentProfile')
            ->where('role_id', 4)->where('id', $id)->first();
        
        if (!$resident){
            return response()->json(['message' => 'Resident not found'], 404);
        }

        return response()->json($resident);
    }

    public function approve($id)
    {   
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden: Admins only'], 403);
        }

        $user = User::findOrFail($id);
        $user->residency_status = 'approved';
        $user->save();

        //Mail::to($user->email)->send(new ResidencyApprovedMail($user));

        return response()->json([
            'message' => 'User approved and email sent.',
            'email_sent_to' => $user->email,
        ]);
    }

    public function reject($id)
    {   
        /** @var User $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden: Admins only'], 403);
        }

        $user = User::findOrFail($id);
        $user->residency_status = 'rejected';
        $user->save();

        //Mail::to($user->email)->send(new ResidencyApprovedMail($user));

        return response()->json([
            'message' => 'User rejected and email sent.',
            'email_sent_to' => $user->email,
        ]);
    }
}
