<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
class VerifyStoreBackup extends Command {
 protected $signature='store:verify-backup {directory}';protected $description='Verify backup hashes and restore into a new isolated MySQL schema; never replace the live database';
 public function handle(){
  $dir=realpath($this->argument('directory'));$base=realpath(storage_path('app/backups'));
  if(!$dir||!$base||strpos(strtolower($dir.DIRECTORY_SEPARATOR),strtolower($base.DIRECTORY_SEPARATOR))!==0){$this->error('Choose a backup directory inside storage/app/backups.');return 1;}
  $manifest=json_decode(file_get_contents($dir.'/manifest.json'),true);$sql=$dir.'/database.sql';if(!$manifest||!is_file($sql)||!hash_equals($manifest['sql_sha256'],hash_file('sha256',$sql))){$this->error('The backup SQL hash does not match.');return 1;}
  foreach($manifest['upload_hashes']??[] as $path=>$hash){$file=realpath($dir.'/uploads/'.$path);if(!$file||strpos(strtolower($file),strtolower($dir.DIRECTORY_SEPARATOR))!==0||!hash_equals($hash,hash_file('sha256',$file))){$this->error('An upload is missing or changed.');return 1;}}
  $db=config('database.connections.mysql');$name='novacart_restorecheck_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));$args=[env('MYSQL_PATH','C:/xampp/mysql/bin/mysql.exe'),'--host='.$db['host'],'--port='.$db['port'],'--user='.$db['username']];$environment=['MYSQL_PWD'=>$db['password']];
  $create=new Process(array_merge($args,['--execute=CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci']),base_path(),$environment);$create->mustRun();
  $input=fopen($sql,'rb');$restore=new Process(array_merge($args,['--database='.$name]),base_path(),$environment);$restore->setInput($input);$restore->setTimeout(300);try{$restore->mustRun();}finally{fclose($input);}
  $check=new Process(array_merge($args,['--database='.$name,'--execute=SELECT COUNT(*) AS products FROM products; SELECT COUNT(*) AS orders FROM orders; SELECT COUNT(*) AS batches FROM product_batches;']),base_path(),$environment);$check->mustRun();
  $this->info('Verified SQL and upload hashes. Restored into isolated schema: '.$name);$this->line($check->getOutput());$this->info('The live database was unchanged. The restore-check schema is retained for inspection.');return 0;
 }
}
