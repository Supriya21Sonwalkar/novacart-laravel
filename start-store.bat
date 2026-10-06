@echo off
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 (
  "C:\xampp1\php\php.exe" artisan serve --host=127.0.0.1 --port=8007
) else (
  php artisan serve --host=127.0.0.1 --port=8007
)
pause
