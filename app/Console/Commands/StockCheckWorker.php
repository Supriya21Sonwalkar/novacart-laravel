<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StockCheckWorker extends Command
{
    protected $signature = 'store:stock-worker {schema} {product}';
    protected $description = 'Internal isolated stock-contention test worker';

    public function handle()
    {
        $schema = $this->argument('schema');
        if (!preg_match('/^novacart_concurrency_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}$/', $schema)) {
            $this->error('Only isolated concurrency schemas are allowed.');
            return 1;
        }
        config(['database.connections.mysql.database' => $schema]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        try {
            DB::transaction(function () {
                $p = \App\Product::where('id', (int) $this->argument('product'))->lockForUpdate()->firstOrFail();
                usleep(500000);
                \App\Services\BatchStock::consume($p, 1);
            });
            $this->line('STOCK_TAKEN');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->line('STOCK_BLOCKED');
        }
        return 0;
    }
}
