<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\AuvoCustomerUpdateCommand::class,
        \App\Console\Commands\FieldControlCustomerUpdateCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('auvo-customer-update');
        $schedule->command('field-control-customer-update');
        $schedule->command('count-data')->everyTenSeconds();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
