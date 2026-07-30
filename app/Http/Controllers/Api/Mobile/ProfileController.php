<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function showPhoto(Request $request, User $user)
    {
        $path = $user->profile_photo_path;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response(
            $path,
            basename($path),
            [
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }

    public function show(Request $request)
    {
        return response()->json([
            'user' => UserResource::make($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => UserResource::make($request->user()->fresh()),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile-photos', 'public');

        $user->update(['profile_photo_path' => $path]);

        $freshUser = $user->fresh();

        return response()->json([
            'message'           => 'Profile photo updated.',
            'profile_photo_url' => UserResource::make($freshUser)->toArray($request)['profile_photo_url'],
            'user'              => UserResource::make($freshUser),
        ]);
    }
}
