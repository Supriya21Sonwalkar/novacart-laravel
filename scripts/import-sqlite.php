<?php
// Import the original local demo into an empty migrated MySQL database.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(config('database.default')!=='mysql'||config('database.connections.mysql.database')!=='novacart')throw new RuntimeException('Expected the local novacart MySQL database.');
$path=__DIR__.'/../database/database.sqlite';
if(!is_file($path))throw new RuntimeException('Original SQLite database not found.');
$source=new PDO('sqlite:'.$path);$source->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$tables=$source->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'")->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table){if(!preg_match('/^[a-z_]+$/',$table)||!Illuminate\Support\Facades\Schema::hasTable($table))throw new RuntimeException('Unexpected source table.');if(Illuminate\Support\Facades\DB::table($table)->exists())throw new RuntimeException('Target already has records; import stopped to avoid overwriting data.');}
$counts=[];
Illuminate\Support\Facades\DB::transaction(function()use($source,$tables,&$counts){foreach($tables as $table){$rows=$source->query('SELECT * FROM "'.$table.'"')->fetchAll(PDO::FETCH_ASSOC);foreach(array_chunk($rows,100) as $batch)Illuminate\Support\Facades\DB::table($table)->insert($batch);$counts[$table]=count($rows);}});
echo 'Imported existing SQLite records: '.array_sum($counts).' rows across '.count($counts).' tables.'.PHP_EOL;
