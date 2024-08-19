<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Auvo\AuvoService;

class AuvoCustomerUpdateCommand extends Command
{
    protected $signature = 'auvo-customer-update';
    protected $description = 'Auvo customer update';

    public function handle()
    {
        $auvoService = new AuvoService();
        [$solidyCustomers, $motoclubCustomers] = $auvoService->getIlevaDatabaseCustomers();
        $auvoService->updateCustomers($solidyCustomers);
        $auvoService->updateCustomers($motoclubCustomers, 'mc');

        $this->info('Auvo customers updated successfully.');
        $this->logExecution();
    }

    protected function logExecution()
    {
        $timestamp = now()->toDateTimeString();
        $this->info("Command 'auvo-customer-update' was executed at {$timestamp}");
    }
}
