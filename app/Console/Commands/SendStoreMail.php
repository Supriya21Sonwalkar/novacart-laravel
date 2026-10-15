<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class SendStoreMail extends Command {protected $signature='store:send-mail';protected $description='Send queued order updates using the configured mail driver';public function handle(){$this->info('Processed notifications: '.\App\Services\OrderMail::process());return 0;}}
