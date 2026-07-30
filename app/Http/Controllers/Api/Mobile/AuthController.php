<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\Mobile\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => $validated['password'],
            'phone'       => $validated['phone'] ?? null,
            'role'        => User::ROLE_TENANT,
            'is_approved' => false,
        ]);

        app()->terminating(function () use ($user): void {
            $this->notificationService->sendSignupPendingApproval($user);
        });

        return response()->json([
            'message' => 'Account created successfully and is pending approval.',
            'user'    => UserResource::make($user),
        ], 202);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid login credentials.'], 422);
        }

        if (! $user->is_approved) {
            return response()->json(['message' => 'Your account is pending approval.'], 403);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => UserResource::make($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => UserResource::make($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }
}
