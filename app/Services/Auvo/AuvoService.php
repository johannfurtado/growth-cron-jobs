<?php

namespace App\Services\Auvo;

use App\Helpers\FormatHelper;
use App\Jobs\UpdateAuvoCustomerJob;
use App\Models\Ileva\IlevaAccidentInvolved;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Octane\Facades\Octane;

class AuvoService
{
    private PendingRequest $authenticatedClient;
    public string $accessToken;

    public function __construct()
    {
        $this->accessToken = (new AuvoAuthService())->getAccessToken();

        $this->authenticatedClient = Http::baseUrl(env('AUVO_API_URL'))
            ->withHeaders($this->getHeaders());
    }

    public function getIlevaDatabaseCustomers(): array
    {

        return Octane::concurrently([
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoToSolidy('ileva_motoclub');
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            },
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoToMotoclub('ileva');
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            },
        ], 10000);
    }

    public function updateCustomers(array $customers, ?string $prefixExternalId = null): void
    {
        foreach ($customers as $customer) {
            UpdateAuvoCustomerJob::dispatch($this->accessToken, $customer, $prefixExternalId);
        }
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
