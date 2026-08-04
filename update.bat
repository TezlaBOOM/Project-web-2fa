@echo off
chcp 65001 > nul
echo ===================================================
echo   ROZPOCZYNANIE AKTUALIZACJI PROJEKTU Z GITHUB
echo ===================================================

:: Sprawdzenie repozytorium Git
if not exist .git (
    echo [BŁĄD] Ten katalog nie jest repozytorium Git!
    pause
    exit /b 1
)

:: 1. Pobranie najnowszego kodu
echo.
echo [1/5] Pobieranie najnowszych zmian z repozytorium Git...
git pull
if %errorlevel% neq 0 (
    echo [BŁĄD] Pobieranie zmian z Git nie powiodło się.
    pause
    exit /b %errorlevel%
)

:: 2. Zależności Composer (PHP)
echo.
echo [2/5] Aktualizowanie zależności Composer (PHP)...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo [BŁĄD] Instalowanie zależności PHP nie powiodło się.
    pause
    exit /b %errorlevel%
)

:: 3. Zależności Node.js i budowanie assetów
echo.
echo [3/5] Instalowanie zależności Node.js i kompilowanie assetów (Vite)...
call npm install --ignore-scripts
if %errorlevel% neq 0 (
    echo [BŁĄD] Instalowanie zależności Node.js nie powiodło się.
    pause
    exit /b %errorlevel%
)
call npm run build
if %errorlevel% neq 0 (
    echo [BŁĄD] Kompilowanie assetów (Vite) nie powiodło się.
    pause
    exit /b %errorlevel%
)

:: 4. Migracje bazy danych
echo.
echo [4/5] Uruchamianie migracji bazy danych...
call php artisan migrate --force
if %errorlevel% neq 0 (
    echo [BŁĄD] Migracja bazy danych nie powiodła się.
    pause
    exit /b %errorlevel%
)

:: 5. Czyszczenie pamięci podręcznej
echo.
echo [5/5] Czyszczenie i optymalizacja pamięci podręcznej (Cache)...
call php artisan cache:clear
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear

echo.
echo ✔ Aktualizacja zakończona pomyślnie! Aplikacja jest gotowa do działania.
pause
