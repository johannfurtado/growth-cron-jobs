<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ileva\IlevaAssociateVehicle;
use App\Jobs\UpdateFieldControlCustomerJob;

class FieldControlCustomerUpdateCommand extends Command
{
    protected $signature = 'field-control-customer-update';
    protected $description = 'Field Control customer update';

    public function handle()
    {
        $customers = IlevaAssociateVehicle::getVehiclesForFieldControl();

        foreach ($customers as $customer) {
            UpdateFieldControlCustomerJob::dispatch($customer);
        }

        $this->info('Field Control customers update jobs dispatched successfully.');
    }
}
