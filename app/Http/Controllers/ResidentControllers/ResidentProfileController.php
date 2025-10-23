<?php

namespace App\Http\Controllers\ResidentControllers; 
use Illuminate\Http\Request;
use App\Http\Controllers\Controller; 
use App\Models\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Image;
use App\Models\UserImage;
use Illuminate\Support\Facades\Hash;
use App\Models\ResidentProfile;

class ResidentProfileController extends Controller
{
    public function show($id)
    {
        $resident = User::where('id', $id)
            ->where('role_id', 4) 
            ->with('userImage.image')
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
            'profile_image_url' => $resident->userImage && $resident->userImage->image
                ? $resident->userImage->image->file_path
                : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $resident = User::where('id', $id)->where('role_id', 4)->first();

        if (!$resident) {
            return response()->json(['message' => 'Resident not found'], 404);
        }

        $validatedUser = $request->validate([
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'address' => 'string|nullable',
            'email' => 'email',
            'contact_num' => 'string|nullable',
            'age' => 'integer|nullable',
            'birthdate' => 'date|nullable',
        ]);

        $resident->update($validatedUser);

        if ($resident->residentProfile) {
            $resident->residentProfile->update([
                'full_name' => $validatedUser['first_name'] . ' ' . $validatedUser['last_name'],
                'address' => $validatedUser['address'] ?? $resident->residentProfile->address,
                'birthdate' => $validatedUser['birthdate'] ?? $resident->residentProfile->birthdate,
            ]);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $resident,
            'resident_profile' => $resident->residentProfile,
        ]);
    }

    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:51200', 
        ]);

        try {
            $user = Auth::user();

            $path = $request->file('image')->store('user_images', 'public');
            $fileName = $request->file('image')->getClientOriginalName();

            $image = Image::create([
                'file_name' => $fileName,
                'file_path' => '/storage/' . $path,
                'uploaded_by' => $user->id,
            ]);

            UserImage::updateOrCreate(
                ['user_id' => $user->id],
                ['image_id' => $image->id]
            );

            return response()->json([
                'message' => 'Profile image updated successfully',
                'image' => [
                    'id' => $image->id,
                    'file_path' => $image->file_path,
                    'file_name' => $image->file_name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'error' => 'Failed to update profile image',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }
}