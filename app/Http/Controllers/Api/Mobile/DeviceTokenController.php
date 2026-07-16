<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Mobile\DeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(DeviceTokenRequest $request)
    {
        $validated = $request->validated();
        $user      = $request->user();

        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id'     => $user->id,
                'platform'    => $validated['platform'],
                'device_name' => $validated['device_name'] ?? null,
            ]
        );

        return response()->json(['message' => 'Device token registered.'], 201);
    }

    public function destroy(Request $request, string $token)
    {
        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $token)
            ->delete();

        return response()->json(['message' => 'Device token removed.']);
    }
}
