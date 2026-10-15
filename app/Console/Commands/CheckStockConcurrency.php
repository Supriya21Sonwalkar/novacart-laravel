<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
class CheckStockConcurrency extends Command {
 protected $signature='store:check-concurrency';protected $description='Run two competing stock transactions in a new isolated MySQL schema';
 public function handle(){
  if(DB::connection()->getDriverName()!=='mysql'){$this->error('MySQL is required for the row-lock test.');return 1;}
  $original=DB::getDefaultConnection();$schema='novacart_concurrency_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));DB::statement('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  config(['database.connections.stockcheck'=>array_merge(config('database.connections.mysql'),['database'=>$schema])]);
  try{
   Artisan::call('migrate',['--database'=>'stockcheck','--force'=>true]);DB::setDefaultConnection('stockcheck');
   $p=\App\Product::create(['name'=>'Isolated contention test','sku'=>'CONCURRENCY-CHECK','price'=>100,'stock'=>0,'active'=>true]);DB::transaction(function()use($p){\App\Services\BatchStock::receive($p,1,['batch_number'=>'CONCURRENCY-1','received_at'=>now(),'unit_cost'=>50]);});
   $args=[PHP_BINARY,base_path('artisan'),'store:stock-worker',$schema,(string)$p->id];$a=new Process($args,base_path());$b=new Process($args,base_path());$a->setTimeout(60);$b->setTimeout(60);$a->start();$b->start();$a->wait();$b->wait();
   $outputs=[$a->getOutput(),$b->getOutput()];$taken=count(array_filter($outputs,function($s){return strpos($s,'STOCK_TAKEN')!==false;}));$blocked=count(array_filter($outputs,function($s){return strpos($s,'STOCK_BLOCKED')!==false;}));
   if(!$a->isSuccessful()||!$b->isSuccessful()||$taken!==1||$blocked!==1||\App\Product::find($p->id)->stock!==0||(int)DB::table('product_batches')->sum('remaining_quantity')!==0||DB::table('batch_allocations')->count()!==1){$this->error('Concurrency check failed. Inspect isolated schema '.$schema);return 1;}
   $this->info('PASS: one worker took the last unit, the other was blocked. Stock and allocation remained consistent.');$this->info('Isolated schema retained: '.$schema);return 0;
  }finally{DB::setDefaultConnection($original);}
 }
}
