$ErrorActionPreference='Stop'
$taskRoot=Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $taskRoot
if(-not (Test-Path -LiteralPath '.env')){Copy-Item -LiteralPath '.env.example' -Destination '.env'}
$taskEnv=Get-Content -Raw -LiteralPath '.env'
$taskPairs=@(@('APP_NAME','NovaCart'),@('APP_ENV','local'),@('APP_DEBUG','false'),@('APP_URL','http://127.0.0.1:8007'),@('MAIL_MAILER','log'),@('MAIL_FROM_ADDRESS','noreply@novacart.test'))
if($taskEnv -notmatch '(?m)^DB_CONNECTION=mysql\s*$'){
 $taskDatabase=(Join-Path $taskRoot 'database/database.sqlite').Replace('\','/')
 if(-not(Test-Path -LiteralPath $taskDatabase)){New-Item -ItemType File -Path $taskDatabase | Out-Null}
 $taskPairs+=,@('DB_CONNECTION','sqlite')
 $taskPairs+=,@('DB_DATABASE',$taskDatabase)
}
foreach($taskPair in $taskPairs){
 $taskEnv=[regex]::Replace($taskEnv,('(?m)^'+$taskPair[0]+'=.*$'),($taskPair[0]+'='+$taskPair[1]))
}
Set-Content -LiteralPath '.env' -Value $taskEnv
if($taskEnv -notmatch '(?m)^APP_KEY=base64:'){
 php artisan key:generate --force
 if($LASTEXITCODE -ne 0){throw 'Application key generation failed.'}
}
php artisan migrate --seed --force
if($LASTEXITCODE -ne 0){throw 'Database setup failed.'}
php artisan storage:link
Write-Output 'Ready. Start with: php artisan serve --host=127.0.0.1 --port=8007'
