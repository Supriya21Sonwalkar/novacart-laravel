$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpPath = 'C:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $phpPath)) { throw 'PHP 7.2 is required. Check your XAMPP installation at C:\xampp.' }
if (-not (Test-Path -LiteralPath '.env')) { throw 'The existing .env configuration is missing. Do not reset your database.' }
if (-not (Test-Path -LiteralPath 'vendor\autoload.php')) { throw 'Composer dependencies are missing. Run composer install using PHP 7.2.' }
Write-Output 'NovaCart uses the database configured in .env. Start MySQL in XAMPP first.'
Write-Output 'Open http://127.0.0.1:8000/ and keep this terminal open.'
$jobRunner = Start-Process -FilePath $phpPath -ArgumentList 'artisan','store:work' -WorkingDirectory $PSScriptRoot -WindowStyle Hidden -PassThru
try {
    & $phpPath artisan serve --host=127.0.0.1 --port=8000
} finally {
    Stop-Process -Id $jobRunner.Id -ErrorAction SilentlyContinue
}
