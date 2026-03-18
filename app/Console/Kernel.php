<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SyncMetrcInventory;
use App\Console\Commands\SyncMetrcPackages;
use App\Models\Organization;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int,string>
     */
    protected $commands = [
        SyncMetrcInventory::class,
        SyncMetrcPackages::class,
    ];

    /**
     * Define the application's command schedule.
     */
  // app/Console/Kernel.php


protected function schedule(Schedule $schedule)
{
    // Pull active packages hourly; prevent overlap for 60 minutes
    $schedule->command('metrc:sync-packages')
        ->hourlyAt(10)
        ->withoutOverlapping(60)
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/metrc-packages.log'));

    // Refresh inventory-linked packages nightly
    $schedule->command('metrc:sync-inventory')
        ->dailyAt('23:00')
        ->withoutOverlapping(120)
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/metrc-sync.log'));
}


    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
