# 🚀 Project Web 2FA — Instrukcja uruchomienia

System zarządzania oparty na Laravel 12 z uwierzytelnianiem dwuskładnikowym (2FA via e-mail).

---

## 📋 Wymagania systemowe

Przed rozpoczęciem upewnij się, że na urządzeniu są zainstalowane:

| Narzędzie | Minimalna wersja | Sprawdzenie |
|-----------|-----------------|-------------|
| **PHP** | 8.3+ | `php -v` |
| **Composer** | 2.x | `composer -V` |
| **Node.js** | 18+ | `node -v` |
| **npm** | 9+ | `npm -v` |
| **MySQL** / MariaDB | 8.0+ / 10.4+ | `mysql --version` |

> 💡 **Zalecane środowisko lokalne:** [ServBay](https://www.servbay.dev/) (macOS), [Laravel Herd](https://herd.laravel.com/) lub [Laragon](https://laragon.org/) (Windows) — dostarczają PHP, MySQL i wirtualne hosty out-of-the-box.

---

## ⚡ Szybki start (krok po kroku)

### Krok 1 — Sklonuj lub skopiuj projekt

```bash
# Opcja A: klonowanie przez Git
git clone <adres-repozytorium> projekt-2fa
cd projekt-2fa

# Opcja B: skopiowanie folderu
# Skopiuj cały folder projektu do katalogu roboczego serwera,
# np. /Applications/ServBay/www/  lub  C:/laragon/www/
```

---

### Krok 2 — Zainstaluj zależności PHP

```bash
composer install
```

> Komenda pobiera wszystkie pakiety zdefiniowane w `composer.json` do katalogu `vendor/`.

---

### Krok 3 — Utwórz plik konfiguracyjny `.env`

```bash
cp .env.example .env
```

Następnie otwórz plik `.env` i uzupełnij sekcję bazy danych:

```dotenv
APP_NAME="Moja Aplikacja"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_web_2fa   # ← nazwa bazy danych (utwórz ją wcześniej!)
DB_USERNAME=root               # ← twój użytkownik MySQL
DB_PASSWORD=                   # ← twoje hasło MySQL
```

---

### Krok 4 — Utwórz bazę danych

Zaloguj się do MySQL i utwórz pustą bazę:

```sql
CREATE DATABASE project_web_2fa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Możesz też użyć narzędzia graficznego: **phpMyAdmin**, **TablePlus**, **DBeaver** lub **HeidiSQL**.

---

### Krok 5 — Wygeneruj klucz aplikacji

```bash
php artisan key:generate
```

> Komenda wypełnia pole `APP_KEY` w pliku `.env`. Jest to wymagane do szyfrowania sesji i danych.

---

### Krok 6 — Uruchom migracje bazy danych

```bash
php artisan migrate
```

Tworzone są wszystkie tabele:

| Tabela | Opis |
|--------|------|
| `users` | Konta użytkowników (z polami 2FA) |
| `settings` | Ustawienia globalne systemu |
| `user_activities` | Logi aktywności |
| `Departament` | Wydziały / działy |
| `DepartamentUsers` | Przypisania użytkownik–wydział |
| `P_modul` | Moduły uprawnień |
| `P_operacje` | Operacje w modułach |
| `P_access` | Uprawnienia użytkowników |
| `documents` | Dokumenty systemowe |
| `sessions`, `cache`, `jobs` | Tabele systemowe Laravel |

---

### Krok 7 — Wypełnij bazę danymi startowymi (Seeder)

```bash
php artisan db:seed
```

Tworzone są domyślne konta użytkowników:

| E-mail | Hasło | Rola |
|--------|-------|------|
| `admin@admin.com` | `admin` | Administrator |
| `mod@admin.com` | `mod` | Moderator |
| `user@admin.com` | `user` | Użytkownik |

> ⚠️ **Ważne:** Zmień domyślne hasła natychmiast po pierwszym zalogowaniu!

---

### Krok 8 — Zainstaluj zależności Node.js i zbuduj zasoby

```bash
npm install
npm run build
```

> Vite skompiluje pliki CSS i JavaScript do katalogu `public/build/`.

---

### Krok 9 — Uruchom serwer deweloperski

#### Opcja A: wbudowany serwer PHP (najprostszy sposób)

```bash
php artisan serve
```

Aplikacja dostępna pod adresem: **http://127.0.0.1:8000**

#### Opcja B: ServBay / Herd / Laragon (wirtualny host)

Jeśli korzystasz z lokalnego środowiska ze wsparciem dla domen `.test`:

1. Wskaż katalog główny serwera na folder `public/` projektu
2. Ustaw domenę, np. `l-2fa.test`
3. Aplikacja dostępna pod: **https://l-2fa.test/**

#### Opcja C: pełny tryb deweloperski (serwer + hot-reload Vite)

```bash
composer run dev
```

Uruchamia równolegle: serwer PHP, kolejkę zadań i Vite z hot-reload.

---

## 🔧 Konfiguracja 2FA (wysyłka e-maili)

Aplikacja obsługuje konfigurację SMTP bezpośrednio z panelu administratora (zakładka **Ustawienia → Logowanie**). Możesz jednak skonfigurować domyślny mailer w pliku `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io    # np. Mailtrap do testów
MAIL_PORT=2525
MAIL_USERNAME=twoj_login
MAIL_PASSWORD=twoje_haslo
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="System 2FA"
```

> 💡 **Do testów lokalnych** polecamy [Mailtrap](https://mailtrap.io/) lub [Mailpit](https://mailpit.axllent.org/) — przechwytują e-maile bez faktycznego wysyłania.

---

## 🗂️ Struktura katalogów (skrót)

```
projekt-2fa/
├── app/              → Kontrolery, Modele, Maile
├── database/
│   ├── migrations/   → Struktura bazy danych
│   └── seeders/      → Dane startowe
├── public/           → Katalog publiczny (document root serwera!)
│   └── build/        → Skompilowane zasoby (CSS/JS)
├── resources/
│   └── views/        → Szablony Blade (Frontend + Backend)
├── routes/
│   └── web.php       → Definicja tras HTTP
├── storage/
│   └── app/documents/→ Przesyłane pliki (poza public/)
├── .env              → Konfiguracja środowiska (NIE commituj!)
├── .env.example      → Szablon konfiguracji
├── composer.json     → Zależności PHP
└── package.json      → Zależności Node.js
```

> ⚠️ **Document root serwera** musi wskazywać na katalog `public/`, **nie** na główny folder projektu!

---

## 🔑 Pierwsze logowanie

1. Otwórz aplikację w przeglądarce
2. Zaloguj się danymi admina:
   - **E-mail:** `admin@admin.com`
   - **Hasło:** `admin`
3. Przejdź do **Ustawienia → Logowanie**, aby skonfigurować SMTP i opcje 2FA
4. Zmień hasło admina w **Ustawienia → Zmiana hasła**

---

## 🛠️ Przydatne polecenia Artisan

```bash
# Czyszczenie cache konfiguracji (po zmianach w .env)
php artisan config:clear
php artisan cache:clear

# Ponowne uruchomienie migracji (UWAGA: kasuje wszystkie dane!)
php artisan migrate:fresh --seed

# Uruchomienie testów
php artisan test

# Kolejka zadań (jeśli uruchamiasz ręcznie)
php artisan queue:work
```

---

## ❗ Najczęstsze problemy

### Problem: `APP_KEY` is missing
```bash
php artisan key:generate
```

### Problem: brak uprawnień do zapisu w `storage/` lub `bootstrap/cache/`
```bash
chmod -R 775 storage bootstrap/cache
```

### Problem: biała strona / błąd 500
- Sprawdź plik `storage/logs/laravel.log`
- Upewnij się, że `.env` ma poprawne dane do bazy danych
- Sprawdź czy uruchomione są migracje: `php artisan migrate:status`

### Problem: brak stylów (CSS nie działa)
```bash
npm install
npm run build
```
Upewnij się, że katalog `public/build/` istnieje i zawiera skompilowane pliki.

### Problem: e-maile 2FA nie dochodzą
- Sprawdź konfigurację SMTP w panelu: **Ustawienia → Logowanie**
- Tymczasowo ustaw `MAIL_MAILER=log` w `.env` — kody będą zapisywane w `storage/logs/laravel.log`

---

## 📦 Jednorazowa instalacja (skrót — wszystkie kroki razem)

```bash
# 1. Wejdź do folderu projektu
cd /ścieżka/do/projektu

# 2. Zainstaluj wszystko jednym skryptem
composer install
cp .env.example .env
php artisan key:generate
# ← Teraz edytuj .env i uzupełnij dane MySQL ←
php artisan migrate
php artisan db:seed
npm install
npm run build

# 3. Uruchom
php artisan serve
```

---

*Szczegółową dokumentację działania aplikacji znajdziesz w pliku [DOKUMENTACJA.md](DOKUMENTACJA.md).*
