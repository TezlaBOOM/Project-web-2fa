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
                <div class="stat-value">0</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <span>Ostatnia Aktywność Systemu</span>
                {{-- Formularz Wyszukiwania w Logach --}}
                <form method="GET" action="{{ route('dashboard') }}" id="search-form" style="margin: 0; display: flex; align-items: center; gap: 0.65rem;">
                    <div style="position: relative; width: 260px;">
                        <span style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem;">🔍</span>
                        <input type="text" name="search" id="search-input" class="form-control"
                               value="{{ $search ?? '' }}" placeholder="Szukaj w logach..."
                               style="padding-left: 2.2rem; padding-top: 0.4rem; padding-bottom: 0.4rem; font-size: 0.85rem;" autocomplete="off">
                    </div>
                    @if(!empty($search))
                        <a href="{{ route('dashboard') }}"
                           style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.4rem 0.75rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                            ✕ Wyczyść
                        </a>
                    @endif
                </form>
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
                    
                    @if($activities->hasPages())
                        <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1rem; flex-wrap: wrap; gap: 0.8rem;">
                            <span style="font-size: 0.82rem; color: var(--text-muted);">
                                Wyświetlono <strong style="color: var(--text-color); font-weight: 500;">{{ $activities->firstItem() }}–{{ $activities->lastItem() }}</strong> z <strong style="color: var(--text-color); font-weight: 500;">{{ $activities->total() }}</strong> aktywności
                            </span>
                            <div style="display: flex; gap: 0.45rem;">
                                @if($activities->onFirstPage())
                                    <span style="padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); opacity: 0.4; border: 1px solid var(--border); pointer-events: none; user-select: none;">Poprzednia</span>
                                @else
                                    <a href="{{ $activities->previousPageUrl() }}" style="padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.18); transition: all 0.15s; font-weight: 500;" onmouseover="this.style.background='rgba(99,102,241,0.15)'; this.style.borderColor='rgba(99,102,241,0.3)'" onmouseout="this.style.background='rgba(99,102,241,0.06)'; this.style.borderColor='rgba(99,102,241,0.18)'">Poprzednia</a>
                                @endif

                                @if($activities->hasMorePages())
                                    <a href="{{ $activities->nextPageUrl() }}" style="padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.18); transition: all 0.15s; font-weight: 500;" onmouseover="this.style.background='rgba(99,102,241,0.15)'; this.style.borderColor='rgba(99,102,241,0.3)'" onmouseout="this.style.background='rgba(99,102,241,0.06)'; this.style.borderColor='rgba(99,102,241,0.18)'">Następna</a>
                                @else
                                    <span style="padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); opacity: 0.4; border: 1px solid var(--border); pointer-events: none; user-select: none;">Następna</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @else
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak zarejestrowanej aktywności.</p>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('search-input');
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                document.getElementById('search-form').submit();
            }, 400);
        });
    }
</script>
@endpush
