#!/bin/bash

# Zatrzymaj wykonywanie przy jakimkolwiek błędzie
set -e

# Kolory do konsoli
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Brak koloru

echo -e "${BLUE}=== ROZPOCZYNANIE AKTUALIZACJI PROJEKTU Z GITHUB ===${NC}"

# Sprawdzenie czy jesteśmy w repozytorium gita
if [ ! -d .git ]; then
    echo -e "${RED}Błąd: Ten katalog nie jest repozytorium Git!${NC}"
    exit 1
fi

# 1. Pobranie najnowszego kodu
echo -e "\n${YELLOW}[1/5] Pobieranie najnowszych zmian z repozytorium Git...${NC}"
git pull

# 2. Zależności Composer (PHP)
echo -e "\n${YELLOW}[2/5] Aktualizowanie zależności Composer (PHP)...${NC}"
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Zależności Node.js i budowanie assetów
echo -e "\n${YELLOW}[3/5]/ Instalowanie zależności Node.js i kompilowanie assetów (Vite)...${NC}"
npm install --ignore-scripts
npm run build

# 4. Migracje bazy danych
echo -e "\n${YELLOW}[4/5] Uruchamianie migracji bazy danych...${NC}"
php artisan migrate --force

# 5. Czyszczenie pamięci podręcznej
echo -e "\n${YELLOW}[5/5] Czyszczenie i optymalizacja pamięci podręcznej (Cache)...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo -e "\n${GREEN}✔ Aktualizacja zakończona pomyślnie! Aplikacja jest gotowa do działania.${NC}"
