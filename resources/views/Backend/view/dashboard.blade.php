@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Podgląd')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Witaj, {{ auth()->user()->name ?? 'Użytkowniku' }}!</h1>
                <p>Przeglądaj dane systemu. Rola: <strong style="color: var(--primary);">Podgląd (View)</strong></p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Tryb tylko do odczytu
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Zarejestrowani Użytkownicy</div>
                <div class="stat-value">{{ $usersCount ?? 0 }}</div>
                <div style="margin-top: 0.5rem;">
                    <a href="{{ route('users.index') }}" style="color: var(--primary); font-size: 0.8rem; text-decoration: none;">Przejdź do listy użytkowników →</a>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Wydziały</div>
                <div class="stat-value">{{ $departmentsCount ?? 0 }}</div>
                <div style="margin-top: 0.5rem;">
                    <a href="{{ route('departments.index') }}" style="color: var(--primary); font-size: 0.8rem; text-decoration: none;">Przejdź do listy wydziałów →</a>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Przypisane Uprawnienia</div>
                <div class="stat-value" style="color: var(--success);">{{ $accessesCount ?? 0 }}</div>
                <div style="margin-top: 0.5rem;">
                    <a href="{{ route('access.index') }}" style="color: var(--primary); font-size: 0.8rem; text-decoration: none;">Przeglądaj uprawnienia użytkowników →</a>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                Informacje o uprawnieniach roli Podgląd
            </div>
            <div class="card-body" style="padding: 1.25rem;">
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin: 0;">
                    Posiadasz uprawnienia w trybie <strong>tylko do odczytu</strong>. Masz możliwość weryfikacji przynależności użytkowników do wydziałów, sprawdzania przypisanych modułów oraz ich terminów ważności. Modyfikacja, dodawanie oraz usuwanie danych są zablokowane.
                </p>
            </div>
        </div>
    </main>
@endsection
