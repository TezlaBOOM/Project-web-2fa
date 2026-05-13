@extends('Backend.layouts.app')
@section('title', 'Ustawienia - Użytkownik')

@section('content')
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">Moja Aplikacja</div>
        
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link">
                Panel Główny
            </a>
            <a href="#" class="nav-link">
                Profil
            </a>
            <a href="{{ route('settings') }}" class="nav-link active">
                Ustawienia
            </a>
        </nav>

        <div class="mt-auto">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Wyloguj się</button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Ustawienia konta</h1>
                <p>Zarządzaj swoim kontem i zabezpieczeniami.</p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Użytkownik
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Zmiana hasła
            </div>
            <div class="card-body" style="padding: 1.5rem;">
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
    </main>
@endsection
