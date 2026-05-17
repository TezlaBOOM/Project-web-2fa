@extends('Backend.layouts.app')
@section('title', 'Panel Główny - Admin')

@section('content')
    @include('Backend.admin._sidebar')


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
                <div class="stat-value">{{ $usersCount ?? 0 }}</div>
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
            <div class="activity-list" style="padding: 1rem; overflow-x: auto;">
                @if($activities && $activities->count() > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                                <th style="padding: 0.75rem;">Data</th>
                                <th style="padding: 0.75rem;">Użytkownik</th>
                                <th style="padding: 0.75rem;">Adres IP</th>
                                <th style="padding: 0.75rem;">Akcja / Opis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 0.75rem; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                                    <td style="padding: 0.75rem; font-weight: 500;">
                                        {{ $activity->user ? $activity->user->name : 'Gość / System' }}
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $activity->user ? $activity->user->email : '' }}</div>
                                    </td>
                                    <td style="padding: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">{{ $activity->ip_address ?? 'Brak' }}</td>
                                    <td style="padding: 0.75rem;">
                                        <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">{{ $activity->action }}</div>
                                        <div style="font-size: 0.85rem; color: var(--text-main);">{{ $activity->description }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 1rem;">
                        {{ $activities->links() }}
                    </div>
                @else
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak zarejestrowanej aktywności.</p>
                @endif
            </div>
        </div>
    </main>
@endsection
