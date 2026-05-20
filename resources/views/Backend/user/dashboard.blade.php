@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Użytkownik')

@section('content')
    <!-- Sidebar -->
    @include('Backend.user._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Witaj, {{ auth()->user()->name ?? 'Gościu' }}!</h1>
                <p>Oto podsumowanie twojego konta na dziś. Rola: <strong style="color: var(--primary);">{{ ucfirst(auth()->user()->role ?? 'Brak') }}</strong></p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Użytkownik
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Ostatnie logowanie</div>
                <div class="stat-value">Dzisiaj</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Status konta</div>
                <div class="stat-value" style="color: var(--success);">Aktywne</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Wiadomości</div>
                <div class="stat-value">0</div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                Twoje Uprawnienia (Moduły i Operacje)
            </div>
            <div class="activity-list" style="padding: 1rem; overflow-x: auto;">
                @if(isset($accesses) && $accesses->count() > 0)
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                                <th style="padding: 0.75rem;">Moduł</th>
                                <th style="padding: 0.75rem;">Zagnieżdżenie</th>
                                <th style="padding: 0.75rem;">Dozwolona Operacja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accesses as $access)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 0.75rem; font-weight: 500;">
                                        {{ $access->modul->nazwa ?? 'Brak' }}
                                    </td>
                                    <td style="padding: 0.75rem; color: var(--text-muted);">
                                        {{ isset($access->modul) && $access->modul->pozycja > 0 ? 'Podkategoria (' . $access->modul->pozycja . ')' : 'Kategoria główna (0)' }}
                                    </td>
                                    <td style="padding: 0.75rem; color: var(--primary);">
                                        {{ $access->operacja->nazwa ?? 'Brak' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak przypisanych uprawnień.</p>
                @endif
            </div>
        </div>
    </main>
@endsection
