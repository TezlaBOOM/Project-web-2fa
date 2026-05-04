@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Admin')

@section('content')
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">Moja Aplikacja</div>
        
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link active">
                Panel Główny
            </a>
            <a href="#" class="nav-link">
                Użytkownicy
            </a>
            <a href="#" class="nav-link">
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
                <h1>Witaj, {{ auth()->user()->name ?? 'Gościu' }}!</h1>
                <p>Oto podsumowanie twojego konta na dziś. Rola: <strong style="color: var(--primary);">{{ ucfirst(auth()->user()->role ?? 'Brak') }}</strong></p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Administrator
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Zarejestrowani Użytkownicy</div>
                <div class="stat-value">124</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Status Serwera</div>
                <div class="stat-value" style="color: var(--success);">Online</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Zgłoszenia</div>
                <div class="stat-value">3</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Ostatnia Aktywność Systemu
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">✓</div>
                    <div class="activity-details">
                        <h4>Nowy użytkownik zarejestrowany</h4>
                        <p>Jan Kowalski dołączył do platformy</p>
                    </div>
                    <div class="activity-time">5 minut temu</div>
                </div>
            </div>
        </div>
    </main>
@endsection
