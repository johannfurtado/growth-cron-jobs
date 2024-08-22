<?php

namespace App\Console\Commands;

use App\DTO\AuvoCustomerDTO;
use App\DTO\AuvoTaskDTO;
use App\Jobs\UpdateAuvoCustomerJob;
use App\Jobs\UpdateAuvoTaskJob;
use App\Services\Auvo\AuvoAuthService;
use Illuminate\Console\Command;
use App\Services\Auvo\AuvoService;
use Laravel\Octane\Facades\Octane;

class HandleAuvoUpdatesForExpertiseAccountCommand extends Command
{
    protected $signature = 'auvo-customer-update';
    protected $description = 'Auvo customer update';

    const ID_USER_FROM = 170642;

    public function handle()
    {
        $accessTokenForAuvoAPI = (new AuvoAuthService(
            env('AUVO_API_KEY_INSPECTION'),
            env('AUVO_API_TOKEN_INSPECTION')
        ))->getAccessToken();

        $auvoService = new AuvoService($accessTokenForAuvoAPI);
        $solidyCustomers = $auvoService->getIlevaDatabaseCustomersForExpertiseAuvoAccount();


        foreach ($solidyCustomers as $customer) {
            $auvoService->updateCustomerWithTasks(
                updateAuvoCustomerJob: new UpdateAuvoCustomerJob(
                    accessToken: $accessTokenForAuvoAPI,
                    auvoCustomerDTO: new AuvoCustomerDTO(
                        externalId: $customer->external_id,
                        description: $customer->description,
                        name: $customer->name,
                        address: $customer->address,
                        manager: $customer->manager,
                        note: $customer->note,
                        phoneNumber: $customer->phone_number,
                    ),
                ),
                updateAuvoTaskJob: new UpdateAuvoTaskJob(
                    accessToken: $accessTokenForAuvoAPI,
                    auvoTaskDTO: new AuvoTaskDTO(
                        externalId: $customer->external_id,
                        idUserFrom: self::ID_USER_FROM,
                        orientation: 'DO IT',
                        address: $customer->address,
                    )
                )
            );
        }

        $this->info('Auvo customers updated successfully.');
        $this->logExecution();
    }

    protected function logExecution()
    {
        $timestamp = now()->toDateTimeString();
        $this->info("Command 'auvo-customer-update' was executed at {$timestamp}");
    }
}
