<?php

namespace App\Services\Auvo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AuvoAuthService
{
    private PendingRequest $client;
    private array $authencationData;
    public function __construct()
    {
        $this->client = Http::baseUrl(env('AUVO_API_URL'))
            ->withHeaders([
                "Content-Type" => "application/json",
            ]);

        $this->authencationData = $this->authenticate();
    }

    public function getAccessToken(): string
    {
        return $this->authencationData['accessToken'];
    }

    private function authenticate(): array
    {
        try {
            $response = $this->client->get('login', [
                'apiKey' => env('AUVO_API_KEY'),
                'apiToken' => env('AUVO_API_TOKEN'),
            ]);

            return $response->json()['result'];
        } catch (\Exception $e) {
            dd($e);
            throw $e;
        }
    }
}
