<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
class WorkStoreSchedule extends Command {
 protected $signature='store:work';protected $description='Run the local store scheduler once each minute; stop with Ctrl+C';
 public function handle(){while(true){Artisan::call('schedule:run');$this->line(Artisan::output());sleep(60);}}
}
