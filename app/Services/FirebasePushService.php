<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebasePushService
{
    public function enabled(): bool
    {
        return ! empty(config('services.firebase.project_id'))
            && ! empty(config('services.firebase.client_email'))
            && ! empty(config('services.firebase.private_key'));
    }

    public function sendToUsers(iterable $users, string $title, string $body, array $data = [], ?string $link = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $userIds = collect($users)
            ->filter(fn ($user): bool => $user instanceof User)
            ->map(fn (User $user): int => (int) $user->id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $tokens = DeviceToken::query()
            ->whereIn('user_id', $userIds->all())
            ->get(['id', 'token', 'platform']);

        if ($tokens->isEmpty()) {
            return;
        }

        $projectId = (string) config('services.firebase.project_id');
        $accessToken = $this->accessToken();

        if (! $accessToken) {
            return;
        }

        $normalizedData = $this->normalizeData($data);

        foreach ($tokens as $tokenModel) {
            $payload = [
                'message' => [
                    'token' => $tokenModel->token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $normalizedData,
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            if ($link) {
                $payload['message']['webpush'] = [
                    'fcm_options' => [
                        'link' => $link,
                    ],
                ];
            }

            $response = Http::timeout(15)
                ->withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                continue;
            }

            $bodyData = $response->json();
            Log::warning('FCM push request failed.', [
                'status' => $response->status(),
                'platform' => $tokenModel->platform,
                'token_id' => $tokenModel->id,
                'response' => $bodyData,
            ]);

            if ($this->shouldDeleteToken($response->status(), $bodyData)) {
                $tokenModel->delete();
            }
        }
    }

    private function accessToken(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        return Cache::remember('firebase_access_token', now()->addMinutes(50), function (): ?string {
            $clientEmail = (string) config('services.firebase.client_email');
            $privateKey = (string) config('services.firebase.private_key');

            $jwt = $this->buildJwt($clientEmail, $privateKey);

            if (! $jwt) {
                return null;
            }

            $response = Http::asForm()
                ->timeout(15)
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                Log::warning('Unable to fetch Firebase OAuth token.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    private function buildJwt(string $clientEmail, string $privateKey): ?string
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->base64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature = '';

        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::warning('Unable to sign Firebase JWT assertion.');

            return null;
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return $normalized;
    }

    private function shouldDeleteToken(int $statusCode, array|string|null $bodyData): bool
    {
        if ($statusCode === 404) {
            return true;
        }

        $serialized = is_array($bodyData) ? json_encode($bodyData) : (string) $bodyData;

        return str_contains($serialized, 'UNREGISTERED') || str_contains($serialized, 'registration-token-not-registered');
    }
}
