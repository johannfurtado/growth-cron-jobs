<?php

namespace App\Services\Auvo;

use App\DTO\AuvoCustomerDTO;
use App\Helpers\FormatHelper;
use App\Jobs\UpdateAuvoCustomerJob;
use App\Jobs\UpdateAuvoTaskJob;
use App\Models\Ileva\IlevaAccidentInvolved;
use App\Models\Ileva\IlevaAssociateVehicle;
use Laravel\Octane\Facades\Octane;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

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
        return Octane::concurrently([
            function () {
                try {
                    return IlevaAccidentInvolved::getAccidentInvolvedForAuvoExpertiseInSolidy();
                } catch (\Exception $e) {
                    return [];
                }
            },
        ], 50000);
    }

    public function updateCustomer(
        AuvoCustomerDTO $auvoCustomerDTO,
        ?Collection $tasksData = null,
    ): void {
        dispatch(new UpdateAuvoCustomerJob(
            $this->accessToken,
            $auvoCustomerDTO,
            $tasksData,
        ));
    }

    public function updateCustomerWithTasks(
        UpdateAuvoCustomerJob $updateAuvoCustomerJob,
        UpdateAuvoTaskJob $updateAuvoTaskJob,
    ): void {
        Bus::chain([
            $updateAuvoCustomerJob,
            $updateAuvoTaskJob,
        ])->dispatch();
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}
