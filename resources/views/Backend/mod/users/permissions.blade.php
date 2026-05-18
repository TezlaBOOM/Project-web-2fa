@extends('Backend.layouts.app')
@section('title', 'Uprawnienia użytkownika: ' . $targetUser->name)

@section('content')
    @include('Backend.mod._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Uprawnienia użytkownika</h1>
                <p>Podgląd przypisanych modułów i operacji.</p>
            </div>
            <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;">
                ← Powrót do listy użytkowników
            </a>
        </div>

        {{-- Karta użytkownika --}}
        <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #818cf8); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.3rem; color: white; flex-shrink: 0;">
                    {{ strtoupper(substr($targetUser->name, 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 1.1rem; color: var(--text-color);">{{ $targetUser->name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                        {{ $targetUser->email }}
                        &bull;
                        <span style="color: var(--primary);">{{ ucfirst($targetUser->role) }}</span>
                        &bull;
                        @if($targetUser->is_active)
                            <span style="color: var(--success);">● Aktywny</span>
                        @else
                            <span style="color: var(--danger);">● Zablokowany</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Uprawnienia pogrupowane po module --}}
        @if($accesses->isEmpty())
            <div class="card" style="padding: 2.5rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔒</div>
                <div style="color: var(--text-muted); font-size: 0.95rem;">Ten użytkownik nie ma przypisanych żadnych uprawnień.</div>
            </div>
        @else
            @php
                $grouped = $accesses->groupBy(fn($a) => $a->modul->nazwa ?? 'Nieprzypisany moduł');
            @endphp

            <div style="display: grid; gap: 1rem;">
                @foreach($grouped as $moduleName => $moduleAccesses)
                    <div class="card" style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.85rem;">
                            <span style="font-size: 1rem;">📁</span>
                            <span style="font-weight: 600; color: var(--primary); font-size: 0.95rem;">{{ $moduleName }}</span>
                            <span style="font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.06); padding: 0.15rem 0.5rem; border-radius: 999px;">
                                {{ $moduleAccesses->count() }} {{ $moduleAccesses->count() === 1 ? 'operacja' : 'operacje' }}
                            </span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            @foreach($moduleAccesses as $access)
                                <span style="background: rgba(99, 102, 241, 0.12); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.82rem; font-weight: 500;">
                                    {{ $access->operacja->nazwa ?? '—' }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem; text-align: right;">
                Łącznie {{ $accesses->count() }} {{ $accesses->count() === 1 ? 'uprawnienie' : 'uprawnień' }} w {{ $grouped->count() }} {{ $grouped->count() === 1 ? 'module' : 'modułach' }}.
            </div>
        @endif
    </main>
@endsection
