<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

class BackupStore extends Command
{
    protected $signature = 'store:backup';
    protected $description = 'Back up MySQL and public uploads without resetting the database';

    private function checksum($path)
    {
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new \RuntimeException('Cannot verify backup file hash.');
        }
        return $hash;
    }

    public function handle()
    {
        if (config('database.default') !== 'mysql') {
            $this->error('This command requires the configured MySQL database.');
            return 1;
        }
        $handle = null;
        try {
            $db = config('database.connections.mysql');
            $root = storage_path('app/backups/' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8));
            if (!\Illuminate\Support\Facades\File::makeDirectory($root, 0700, true)) {
                throw new \RuntimeException('Cannot create backup directory.');
            }
            $path = $root . '/database.sql';
            $handle = fopen($path . '.part', 'wb');
            if (!$handle) {
                throw new \RuntimeException('Cannot create backup file.');
            }
            $process = new Process([
                env('MYSQLDUMP_PATH', 'C:/xampp/mysql/bin/mysqldump.exe'),
                '--host=' . $db['host'],
                '--port=' . $db['port'],
                '--user=' . $db['username'],
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                $db['database'],
            ], base_path(), ['MYSQL_PWD' => $db['password']]);
            $process->setTimeout(300);
            $process->run(function ($type, $data) use ($handle) {
                if ($type === Process::OUT && fwrite($handle, $data) !== strlen($data)) {
                    throw new \RuntimeException('Incomplete backup write.');
                }
            });
            fclose($handle);
            $handle = null;
            if (!$process->isSuccessful()) {
                throw new \RuntimeException('Database dump failed.');
            }
            if (!rename($path . '.part', $path)) {
                throw new \RuntimeException('Cannot finalize backup SQL.');
            }
            $uploads = storage_path('app/public');
            if (is_dir($uploads) && !\Illuminate\Support\Facades\File::copyDirectory($uploads, $root . '/uploads')) {
                throw new \RuntimeException('Upload backup failed.');
            }
            $files = [];
            if (is_dir($root . '/uploads')) {
                foreach (\Illuminate\Support\Facades\File::allFiles($root . '/uploads') as $file) {
                    $files[$file->getRelativePathname()] = $this->checksum($file->getPathname());
                }
            }
            $manifest = json_encode([
                'database' => $db['database'],
                'created_at' => now()->toIso8601String(),
                'sql_sha256' => $this->checksum($path),
                'uploads_included' => is_dir($root . '/uploads'),
                'upload_hashes' => $files,
            ], JSON_PRETTY_PRINT);
            if ($manifest === false || file_put_contents($root . '/manifest.json.part', $manifest) === false || !rename($root . '/manifest.json.part', $root . '/manifest.json')) {
                throw new \RuntimeException('Cannot finalize backup manifest.');
            }
            $this->info('Backup created: ' . $root);
            return 0;
        } catch (Throwable $e) {
            report($e);
            $this->error('Backup failed. Any incomplete files were retained; no completed backup was announced.');
            return 1;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }
}
