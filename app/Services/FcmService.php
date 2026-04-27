<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmService
{
    private string $projectId = 'keninfotec-1f7c5';

    private function accessToken(): string
    {
        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            json_decode(
                file_get_contents(storage_path('app/firebase-service-account.json')),
                true
            )
        );

        return $credentials->fetchAuthToken()['access_token'];
    }

    public function sendPush(string $token, string $title, string $body): void
    {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high'
                    ],
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'https://yourdomain.com/icon.png',
                    ]
                ]
            ]
        ];

        Http::withToken($this->accessToken())
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $payload
            );
    }
}
