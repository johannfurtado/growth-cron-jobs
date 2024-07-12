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
    }
}
