@extends('Backend.layouts.app')
@section('title', 'Mój Profil - ' . $user->name)

@section('content')
    @php
        $sidebarView = match($user->role) {
            'admin' => 'Backend.admin._sidebar',
            'mod' => 'Backend.mod._sidebar',
            default => 'Backend.user._sidebar',
        };
    @endphp
    @include($sidebarView)

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Mój Profil</h1>
                <p>Podgląd Twoich danych oraz przypisanych modułów i operacji.</p>
            </div>
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako {{ ucfirst($user->role) }}
            </div>
        </div>

        {{-- Karta użytkownika --}}
        <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #818cf8); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.4rem; color: white; flex-shrink: 0;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 1.15rem; color: var(--text-color);">{{ $user->name }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span>{{ $user->email }}</span>
                            <span>&bull;</span>
                            <span style="color: var(--primary); font-weight: 600;">{{ ucfirst($user->role) }}</span>
                            <span>&bull;</span>
                            @if($user->is_active)
                                <span style="color: var(--success); font-weight: 500;">● Aktywny</span>
                            @else
                                <span style="color: var(--danger); font-weight: 500;">● Zablokowany</span>
                            @endif
                        </div>
                        @if($user->departments && $user->departments->isNotEmpty())
                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.5rem; align-items: center;">
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500;">Wydziały:</span>
                                @foreach($user->departments as $dept)
                                    @php
                                        $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                    @endphp
                                    <span title="{{ $dept->Nazwa }}@if($dept->pivot->od) (od: {{ $dept->pivot->od }})@endif @if($dept->pivot->do) (do: {{ $dept->pivot->do }})@endif @if($isExpired) - NIEAKTYWNY @endif"
                                          style="background: {{ $isExpired ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.12)' }}; color: {{ $isExpired ? '#ef4444' : 'var(--success)' }}; border: 1px solid {{ $isExpired ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.2)' }}; padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        <span>{{ $dept->Nazwa }}</span>
                                        @if($dept->pivot->do)
                                            <span style="color: {{ $isExpired ? '#ef4444' : 'inherit' }}; font-weight: {{ $isExpired ? '700' : '500' }}; font-size: 0.68rem;">
                                                (do: {{ $dept->pivot->do }})
                                            </span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <a href="{{ route('settings') }}" style="font-size: 0.85rem; padding: 0.45rem 0.9rem; border-radius: 6px; text-decoration: none; color: var(--text-muted); border: 1px solid var(--border); background: rgba(255,255,255,0.03); display: inline-flex; align-items: center; gap: 0.4rem;">
                        ⚙ Ustawienia konta
                    </a>
                </div>
            </div>
        </div>

        {{-- Nagłówek sekcji uprawnień --}}
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <h2 style="font-size: 1.1rem; font-weight: 600; color: var(--text-color); margin: 0;">
                Moje przypisane uprawnienia
            </h2>
        </div>

        {{-- Uprawnienia pogrupowane po module --}}
        @if($accesses->isEmpty())
            <div class="card" style="padding: 2.5rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔒</div>
                <div style="color: var(--text-muted); font-size: 0.95rem;">Nie masz przypisanych żadnych uprawnień.</div>
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
                                @php $isValid = $access->isValid(); @endphp
                                <span style="background: {{ $isValid ? 'rgba(99, 102, 241, 0.1)' : 'rgba(239, 68, 68, 0.08)' }}; color: {{ $isValid ? 'var(--primary)' : 'var(--danger)' }}; border: 1px solid {{ $isValid ? 'rgba(99, 102, 241, 0.22)' : 'rgba(239, 68, 68, 0.22)' }}; padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.82rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    {{ $access->operacja->nazwa ?? '—' }}
                                    @if($access->valid_from || $access->valid_to)
                                        <span style="font-size: 0.72rem; opacity: 0.8; font-weight: 400;">
                                            ({{ $access->valid_from ? $access->valid_from->format('Y-m-d') : '∞' }} - {{ $access->valid_to ? $access->valid_to->format('Y-m-d') : '∞' }})
                                        </span>
                                    @endif
                                    @if(!$isValid)
                                        <span style="font-size: 0.62rem; background: rgba(239, 68, 68, 0.18); color: var(--danger); padding: 0.05rem 0.3rem; border-radius: 3px; font-weight: 700; text-transform: uppercase;">Wygasło</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 1rem; color: var(--text-muted); font-size: 0.8rem; text-align: right;">
                Łącznie {{ $accesses->count() }} {{ $accesses->count() === 1 ? 'uprawnienie' : 'uprawnień' }} w {{ $grouped->count() }} {{ $grouped->count() === 1 ? 'modułach' : 'modułach' }}.
            </div>
        @endif
    </main>
@endsection
