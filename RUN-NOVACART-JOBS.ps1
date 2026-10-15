$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
Write-Output 'NovaCart local scheduler: payment expiry, order mail and daily backups. Keep this terminal open.'
while ($true) {
    & 'C:\xampp\php\php.exe' artisan schedule:run
    Start-Sleep -Seconds 60
}
