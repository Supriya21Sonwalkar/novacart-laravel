<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class ExpirePayments extends Command {
 protected $signature='store:expire-payments';
 protected $description='Release expired sandbox payment reservations exactly once';
 public function handle(){$this->info('Expired reservations: '.\App\Services\PaymentFlow::expire());return 0;}
}
