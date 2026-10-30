@extends('Backend.layouts.app')
@section('title', 'Ustawienia - Admin')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Ustawienia konta</h1>
                <p>Zarządzaj swoim kontem i zabezpieczeniami.</p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako {{ auth()->user()->role === 'admin' ? 'Administrator' : (auth()->user()->role === 'view' ? 'Podgląd (View)' : ucfirst(auth()->user()->role)) }}
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                Zmiana hasła
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <form action="{{ route('settings.password') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="current_password" class="form-label">Obecne hasło</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nowe hasło</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirmation" class="form-label">Potwierdź nowe hasło</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn-primary">Zmień hasło</button>
                </form>
            </div>
        </div>

        @if($settings['enable_2fa'] ?? false)
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                Uwierzytelnianie Dwuskładnikowe (2FA)
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <p style="margin-bottom: 1.5rem; color: var(--text-muted);">
                    Zabezpiecz swoje konto dodatkowym kodem wysyłanym na e-mail podczas logowania.
                </p>

                <form action="{{ route('settings.2fa.toggle') }}" method="POST">
                    @csrf
                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <input type="checkbox" name="two_factor_enabled" id="two_factor_enabled" value="1" {{ auth()->user()->two_factor_enabled ? 'checked' : '' }} onChange="this.form.submit()">
                        <label for="two_factor_enabled" style="margin-bottom: 0; font-weight: 600;">Włącz weryfikację dwuetapową (2FA)</label>
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if(auth()->user()->role === 'admin')
        <div class="card" id="database-backup" style="margin-top: 2rem;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Kopia zapasowa i Import bazy danych</span>
                <span style="font-size: 0.72rem; background: rgba(99, 102, 241, 0.15); color: var(--primary); padding: 0.25rem 0.65rem; border-radius: 999px; font-weight: 600;">Tylko Administrator</span>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                
                {{-- Sekcja 1: Eksport bazy danych --}}
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📤</span> Eksport bazy danych (Pobieranie kopii zapasowej)
                    </h3>
                    <p style="margin-bottom: 1.25rem; color: var(--text-muted); line-height: 1.5; font-size: 0.875rem;">
                        Pobierz pełną kopię zapasową bazy danych aplikacji w formacie SQL. Plik zawiera definicje wszystkich tabel (strukturę) oraz pełne dane (rekordy), które można zaimportować w razie potrzeby przywrócenia bazy.
                    </p>

                    <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 2rem;">
                        <div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Silnik bazy danych</div>
                            <div style="font-weight: 600; font-size: 0.92rem; margin-top: 0.25rem; color: var(--text-color);">{{ strtoupper(config('database.default')) }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Format eksportu</div>
                            <div style="font-weight: 600; font-size: 0.92rem; margin-top: 0.25rem; color: var(--text-color);">Zrzut SQL (.sql)</div>
                        </div>
                        <div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;">Zakres</div>
                            <div style="font-weight: 600; font-size: 0.92rem; margin-top: 0.25rem; color: var(--text-color);">Pełna baza (wszystkie tabele i dane)</div>
                        </div>
                    </div>

                    <a href="{{ route('settings.database.export') }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; padding: 0.65rem 1.4rem;">
                        <span>💾</span> Pobierz kopię bazy danych (.sql)
                    </a>
                </div>

                <div style="border-top: 1px solid var(--border); margin: 2rem 0;"></div>

                {{-- Sekcja 2: Import bazy danych --}}
                <div>
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📥</span> Import bazy danych (Przywracanie ze zrzutu SQL)
                    </h3>
                    <p style="margin-bottom: 1rem; color: var(--text-muted); line-height: 1.5; font-size: 0.875rem;">
                        Wybierz plik kopii zapasowej <code>.sql</code>, aby przywrócić stan bazy danych. Wszystkie zawarte w pliku instrukcje SQL zostaną wykonane w bazie.
                    </p>

                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); color: #fbbf24; padding: 0.85rem 1.1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; line-height: 1.45;">
                        ⚠️ <strong>Uwaga:</strong> Import bazy danych nadpisze istniejące tabele i dane w systemie! Przed wykonaniem importu zaleca się pobranie aktualnej kopii zapasowej (powyżej).
                    </div>

                    <form action="{{ route('settings.database.import') }}" method="POST" enctype="multipart/form-data" onsubmit="return confirm('Czy na pewno chcesz zaimportować bazę danych z tego pliku?\n\nUWAGA: Istniejące tabele i dane w bazie zostaną nadpisane!');">
                        @csrf
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label for="database_file" class="form-label" style="font-weight: 600; margin-bottom: 0.4rem; display: block;">Wybierz plik zrzutu bazy danych (.sql)</label>
                            <input type="file" name="database_file" id="database_file" class="form-control" accept=".sql" required style="padding: 0.5rem 0.75rem;">
                            <small style="color: var(--text-muted); font-size: 0.78rem; margin-top: 0.35rem; display: block;">
                                Obsługiwany format: pliki <code>.sql</code> (maksymalny rozmiar: 100 MB).
                            </small>
                        </div>

                        <button type="submit" class="btn-primary" style="background: #e11d48; border-color: #e11d48; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.65rem 1.4rem;">
                            <span>📥</span> Przywróć bazę danych (.sql)
                        </button>
                    </form>
                </div>

            </div>
        </div>
        @endif
    </main>
@endsection
