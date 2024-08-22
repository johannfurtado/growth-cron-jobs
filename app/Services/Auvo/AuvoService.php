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

        return [
            IlevaAccidentInvolved::getAccidentInvolvedForAuvoToMotoclub('ileva'),
            IlevaAccidentInvolved::getAccidentInvolvedForAuvoToSolidy('ileva_motoclub'),
            IlevaAccidentInvolved::getAccidentInvolvedForAuvoToNova('ileva_nova'),
        ];
    }

    public function getIlevaDatabaseCustomersForExpertiseAuvoAccount(): array
    {
        return IlevaAssociateVehicle::getVehiclesForAuvo();
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
}
