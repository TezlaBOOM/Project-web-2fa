@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Brak Uprawnień')

@section('content')
    <!-- Main Content -->
    <main class="main-content no-sidebar">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Witaj, {{ auth()->user()->name ?? 'Gościu' }}!</h1>
                <p>Oczekujesz na przypisanie roli przez administratora.</p>
            </div>
            
            <div class="header-actions">
                <a href="{{ route('settings') }}" class="btn-primary" style="text-decoration: none; padding: 0.5rem 1rem; display: inline-block;">Ustawienia</a>
                <div class="status-badge">
                    Konto oczekujące
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout">Wyloguj się</button>
                </form>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Ostatnie logowanie</div>
                <div class="stat-value">Dzisiaj</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Status konta</div>
                <div class="stat-value" style="color: var(--text-muted);">Brak roli</div>
            </div>
        </div>

        <div class="empty-state">
            <h2>Brak dostępu do funkcji</h2>
            <p>Twoje konto zostało pomyślnie utworzone, ale nie ma przypisanej żadnej roli. Skontaktuj się z administratorem, aby uzyskać dostęp do funkcji systemu.</p>
        </div>
    </main>
@endsection
