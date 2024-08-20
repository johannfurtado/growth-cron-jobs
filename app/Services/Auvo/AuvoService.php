<?php

namespace App\Services\Auvo;

use App\DTO\AuvoCustomerDTO;
use App\Helpers\FormatHelper;
use App\Jobs\UpdateAuvoCustomerJob;
use App\Models\Ileva\IlevaAccidentInvolved;
use App\Models\Ileva\IlevaAssociateVehicle;
use Laravel\Octane\Facades\Octane;
use App\Services\Auvo\AuvoData;
use Illuminate\Support\Collection;

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
                    return [];  // Retornar array vazio em caso de falha
                }
            },
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoToMotoclub('ileva');
                } catch (\Exception $e) {
                    return [];
                }
            },
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoToNova('ileva_nova');
                } catch (\Exception $e) {
                    return [];
                }
            },
        ], 50000);
    }

    public function getIlevaDatabaseCustomersForExpertiseAuvoAccount(): array
    {
        return IlevaAssociateVehicle::getVehiclesForAuvo();
    }

    public function updateCustomer(AuvoCustomerDTO $auvoCustomerDTO, ?Collection $tasksData = null): void
    {
        dispatch(new UpdateAuvoCustomerJob(
            $this->accessToken,
            $auvoCustomerDTO,
            $tasksData
        ));
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
