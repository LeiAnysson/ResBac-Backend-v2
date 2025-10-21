<?php

namespace App\Http\Controllers;

use App\Models\ResidentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Role;
use App\Helpers\activity_logger;
use Illuminate\Support\Facades\Password;
use App\Helpers\CryptoHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Events\UserRegistered;
use App\Models\Notification;

class AuthController extends Controller
{
    public function register(request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'birthdate' => 'nullable|date',
            'address' => 'nullable|string',
            'id_image' => 'nullable|image|max:10240', 
            'id_number' => 'nullable|string|max:255',
            'contact_num' => 'nullable|string',
        ]);

        $residentRoleId = Role::where('name', 'Resident')->value('id');

        $age = null;
        if ($request->birthdate) {
            $age = Carbon::parse($request->birthdate)->age;
        }

        $idImagePath = null;
        if ($request->hasFile('id_image')) {
            $file = $request->file('id_image');
            $idImagePath = $file->storeAs(
                'id_images',
                $file->hashName(),
                'public'
            );
        }


        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $residentRoleId,
            'birthdate' => $request->birthdate,
            'address' => $request->address,
            'age' => $age,
            'contact_num' => $request->contact_num,
            'residency_status' => 'pending',
        ]);

        ResidentProfile::create([
            'user_id' => $user->id,
            'id_image_path' => $idImagePath,
            'id_number' => $request->id_number ?? '',
            'full_name' => $request->first_name . ' ' . $request->last_name,
            'address' => $request->address,
            'birthdate' => $request->birthdate,
        ]);

        recordActivity('registered', 'Account ' . $user->id);

        broadcast(new UserRegistered($user))->toOthers();

        $admin = User::where('role_id', Role::where('name', 'Admin')->value('id'))->first();

        if ($admin) {
            Notification::create([
                'user_id' => $admin->id,
                'message' => "New resident registered: {$user->first_name} {$user->last_name}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $token = $user->createToken('resbac-token')->plainTextToken;

        recordActivity('logged in', 'Account', $user->id);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'residency_status' => $user->residency_status,
                'role' => $user->role
                    ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                    ]
                    : null,
            ],
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role');

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->forceFill([
                'last_used_at' => now()
            ])->save();
        }

        $user->name = $user->first_name . ' ' . $user->last_name;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ] : null,
        ]);
    }

    public function showResetForm(Request $request, $id)
    {
        return view('auth.reset_password', ['id' => $id, 'expires' => $request->expires]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid token'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password successfully reset!']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        $user = User::where('email', $request->email)->first();
        $token = Str::random(60);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => now()]
        );

        $resetUrl = env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . $user->email;

        Mail::send('emails.password_reset', ['url' => $resetUrl, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Your Password');
        });

        return response()->json(['message' => 'Password reset link sent! Check your email.']);
    }
}
