<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ApiKeyAdd::class,
        Commands\ApiKeyList::class,
        Commands\ApiKeyRemove::class,
        Commands\ImageCleanUp::class,
        Commands\ImageStats::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        #$schedule->command('image:cleanup')->daily();
        $schedule->command('image:stats')->cron('0 */3 * * *');
    }
}
