<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DriverDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverDeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ]);
        $hash = hash('sha256', $data['token']);

        $request->user()->driverDeviceTokens()->updateOrCreate(
            ['token_hash' => $hash],
            [
                'token' => $data['token'],
                'platform' => $data['platform'],
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);
        DriverDeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token_hash', hash('sha256', $data['token']))
            ->delete();

        return response()->json(['success' => true]);
    }
}
