<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
class BackupStore extends Command {
 protected $signature='store:backup';
 protected $description='Back up MySQL and public uploads without resetting the database';
 public function handle(){
  if(config('database.default')!=='mysql'){$this->error('This command requires the configured MySQL database.');return 1;}
  $db=config('database.connections.mysql');$root=storage_path('app/backups/'.date('Ymd-His').'-'.substr(bin2hex(random_bytes(4)),0,8));\Illuminate\Support\Facades\File::makeDirectory($root,0700,true);
  $path=$root.'/database.sql';$handle=fopen($path.'.part','wb');if(!$handle){$this->error('Cannot create backup file.');return 1;}
  $process=new Process([env('MYSQLDUMP_PATH','C:/xampp/mysql/bin/mysqldump.exe'),'--host='.$db['host'],'--port='.$db['port'],'--user='.$db['username'],'--single-transaction','--quick','--skip-lock-tables',$db['database']],base_path(),['MYSQL_PWD'=>$db['password']]);$process->setTimeout(300);
  try{$process->run(function($type,$data)use($handle){if($type===Process::OUT)fwrite($handle,$data);});}finally{fclose($handle);}
  if(!$process->isSuccessful()){$this->error('Database backup failed. The partial file was retained for diagnosis.');return 1;}
  rename($path.'.part',$path);
  $uploads=storage_path('app/public');if(is_dir($uploads))\Illuminate\Support\Facades\File::copyDirectory($uploads,$root.'/uploads');
  $files=[];if(is_dir($root.'/uploads'))foreach(\Illuminate\Support\Facades\File::allFiles($root.'/uploads') as $file)$files[$file->getRelativePathname()]=hash_file('sha256',$file->getPathname());
  file_put_contents($root.'/manifest.json',json_encode(['database'=>$db['database'],'created_at'=>now()->toIso8601String(),'sql_sha256'=>hash_file('sha256',$path),'uploads_included'=>is_dir($root.'/uploads'),'upload_hashes'=>$files],JSON_PRETTY_PRINT));
  $this->info('Backup created: '.$root);return 0;
 }
}
