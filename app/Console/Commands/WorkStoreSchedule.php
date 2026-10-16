<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WorkStoreSchedule extends Command
{
    protected $signature = 'store:work';
    protected $description = 'Run the local store scheduler once each minute; stop with Ctrl+C';

    public function handle()
    {
        while (true) {
            try {
                $status = Artisan::call('schedule:run');
                $this->line(Artisan::output());
                if ($status !== 0) {
                    $this->error('A scheduled task failed; see the logs.');
                }
            } catch (\Throwable $e) {
                report($e);
                $this->error('Scheduler iteration failed; retrying on the next tick.');
            }
            sleep(60);
        }
    }
}
