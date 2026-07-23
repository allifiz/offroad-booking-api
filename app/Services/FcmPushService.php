<?php

namespace App\Services;

use App\Models\DriverDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $credentialsPath = config('services.fcm.credentials');
        $projectId = config('services.fcm.project_id');
        if (! $credentialsPath || ! $projectId || ! is_file($credentialsPath)) {
            Log::warning('FCM push skipped: credentials are not configured.');

            return;
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true, flags: JSON_THROW_ON_ERROR);
        $accessToken = $this->accessToken($credentials);
        $tokens = DriverDeviceToken::query()->where('user_id', $userId)->get();

        foreach ($tokens as $device) {
            $response = Http::withToken($accessToken)->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                ['message' => [
                    'token' => $device->token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'priority' => 'PRIORITY_HIGH',
                            'sound' => 'default',
                        ],
                    ],
                    'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
                ]],
            );
            if ($response->status() === 404 || str_contains($response->body(), 'UNREGISTERED')) {
                $device->delete();
            }
        }
    }

    private function accessToken(array $credentials): string
    {
        $encode = static fn (array $value): string => rtrim(strtr(base64_encode(json_encode($value)), '+/', '-_'), '=');
        $now = time();
        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);
        openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ])->throw()->json('access_token');
    }
}
