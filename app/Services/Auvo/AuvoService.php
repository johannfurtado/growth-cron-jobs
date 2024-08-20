<?php

namespace App\Services\Auvo;

use App\DTO\AuvoCustomerDTO;
use App\Helpers\FormatHelper;
use App\Jobs\UpdateAuvoCustomerJob;
use App\Models\Ileva\IlevaAccidentInvolved;
use App\Models\Ileva\IlevaAssociateVehicle;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Octane\Facades\Octane;
use App\Services\Auvo\AuvoData;

class AuvoService
{
    public function __construct(
        private readonly string $accessToken
    ) {}

    public function getIlevaDatabaseCustomersForInspectionAuvoAccount(): array
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
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoToNova('ileva_nova');
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            },
        ], 20000);
    }

    public function getIlevaDatabaseCustomersForExpertiseAuvoAccount(): array
    {
        return IlevaAssociateVehicle::getVehiclesForAuvo();
    }

    public function updateCustomer(AuvoCustomerDTO $auvoCustomerDTO, ?string $prefixExternalId = null): void
    {
        $auvoData = new AuvoData();

        UpdateAuvoCustomerJob::dispatch(
            $this->accessToken,
            $auvoCustomerDTO,
            $prefixExternalId,
            $auvoData->getAuvoData()
        );
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
