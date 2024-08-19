<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\AuvoCustomerUpdateCommand::class,
        \App\Console\Commands\FieldControlCustomerUpdateCommand::class,
        \App\Console\Commands\CountData::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('auvo-customer-update')
            ->dailyAt('07:00')
            ->timezone('America/Sao_Paulo');

        $schedule->command('field-control-customer-update')
            ->everyThirtyMinutes();

        $schedule->command('count-data')
            ->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
