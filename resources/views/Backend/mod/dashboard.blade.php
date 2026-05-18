@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Moderator')

@section('content')
    @include('Backend.mod._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Witaj, {{ auth()->user()->name ?? 'Gościu' }}!</h1>
                <p>Oto podsumowanie twojego konta na dziś. Rola: <strong style="color: var(--primary);">{{ ucfirst(auth()->user()->role ?? 'Brak') }}</strong></p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Moderator
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Zarejestrowani Użytkownicy</div>
                <div class="stat-value">{{ $usersCount ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Rozpatrzone dzisiaj</div>
                <div class="stat-value" style="color: var(--success);">5</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Wiadomości</div>
                <div class="stat-value">2</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Ostatnia Aktywność Użytkowników
            </div>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">!</div>
                    <div class="activity-details">
                        <h4>Nowe zgłoszenie od użytkownika</h4>
                        <p>Zgłoszenie naruszenia regulaminu</p>
                    </div>
                    <div class="activity-time">Właśnie teraz</div>
                </div>
            </div>
        </div>
    </main>
@endsection
