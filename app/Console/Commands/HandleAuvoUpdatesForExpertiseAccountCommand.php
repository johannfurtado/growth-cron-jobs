<?php

namespace App\Console\Commands;

use App\DTO\AuvoCustomerDTO;
use App\Jobs\UpdateAuvoTaskJob;
use App\Services\Auvo\AuvoAuthService;
use Illuminate\Console\Command;
use App\Services\Auvo\AuvoService;
use Laravel\Octane\Facades\Octane;

class HandleAuvoUpdatesForExpertiseAccountCommand extends Command
{
    protected $signature = 'auvo-customer-update';
    protected $description = 'Auvo customer update';

    public function handle()
    {
        $accessTokenForAuvoAPI = (new AuvoAuthService(
            env('AUVO_API_KEY_INSPECTION'),
            env('AUVO_API_TOKEN_INSPECTION')
        ))->getAccessToken();

        $auvoService = new AuvoService($accessTokenForAuvoAPI);
        $solidyCustomers = $auvoService->getIlevaDatabaseCustomersForExpertiseAuvoAccount();

        foreach ($solidyCustomers as $customer) {
            $auvoService->updateCustomer(
                auvoCustomerDTO: new AuvoCustomerDTO(
                    externalId: $customer['external_id'],
                    description: $customer['description'],
                    name: $customer['name'],
                    address: $customer['address'],
                    manager: $customer['manager'],
                    note: $customer['note'],
                    phoneNumber: $customer['phone_number'],
                ),
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
