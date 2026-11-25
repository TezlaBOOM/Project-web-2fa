@extends('Backend.layouts.app')
@section('title', 'Podgląd danych — ' . $user->name)

@section('content')
    @if(in_array(auth()->user()->role, ['admin', 'view', 'support']))
        @include('Backend.admin._sidebar')
    @else
        @include('Backend.mod._sidebar')
    @endif

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Podgląd danych użytkownika</h1>
                <p>Informacje o koncie: <strong style="color: var(--text-main);">{{ $user->name }}</strong></p>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('users.index') }}"
                   style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;"
                   onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Powrót do listy
                </a>
                <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                    Zalogowano jako {{ auth()->user()->role === 'admin' ? 'Administrator' : (auth()->user()->role === 'view' ? 'Podgląd (View)' : (auth()->user()->role === 'support' ? 'Wsparcie (Support)' : ucfirst(auth()->user()->role))) }}
                </div>
            </div>
        </div>

        @php
            $roleLabels = [
                'admin' => 'Administrator',
                'mod' => 'Moderator',
                'support' => 'Wsparcie (Support)',
                'view' => 'Podgląd (View)',
                'user' => 'Użytkownik',
                'none' => 'Brak roli',
            ];
        @endphp

        <div class="show-grid">
            <div style="min-width: 0;">
                <div class="card" style="padding: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; padding-bottom: 1.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.35rem; color: white; flex-shrink: 0;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-main);">{{ $user->name }}</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $user->email }}</div>
                        </div>
                    </div>

                    <div style="display: grid; gap: 1.25rem;">
                        <div>
                            <div class="form-label" style="margin-bottom: 0.35rem;">Imię i nazwisko</div>
                            <div style="padding: 0.7rem 0.9rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main); font-weight: 500;">
                                {{ $user->name }}
                            </div>
                        </div>

                        <div>
                            <div class="form-label" style="margin-bottom: 0.35rem;">Adres e-mail</div>
                            <div style="padding: 0.7rem 0.9rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; color: var(--text-main);">
                                {{ $user->email }}
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <div class="form-label" style="margin-bottom: 0.35rem;">Rola w systemie</div>
                                <div style="padding: 0.7rem 0.9rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px;">
                                    <span style="background: rgba(99, 102, 241, 0.12); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                                        {{ $roleLabels[$user->role] ?? ucfirst($user->role ?? 'Brak') }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <div class="form-label" style="margin-bottom: 0.35rem;">Status konta</div>
                                <div style="padding: 0.7rem 0.9rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px;">
                                    @if($user->is_active)
                                        <span style="color: var(--success); font-size: 0.875rem; font-weight: 600;">● Aktywny</span>
                                    @else
                                        <span style="color: var(--danger); font-size: 0.875rem; font-weight: 600;">● Nieaktywny</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-label" style="margin-bottom: 0.5rem;">Wydziały i okresy przynależności</div>
                            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 8px; padding: 0.85rem; display: flex; flex-direction: column; gap: 0.6rem;">
                                @forelse($user->sortedDepartments as $dept)
                                    @php
                                        $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                        $isFuture = $dept->pivot->od && $dept->pivot->od > date('Y-m-d');
                                    @endphp
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.8rem; border-radius: 8px; background: rgba(255,255,255,0.02); border: 1px solid {{ $isExpired ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.2)' }};">
                                        <div>
                                            <div style="font-weight: 600; color: {{ $isExpired ? '#ef4444' : 'var(--text-main)' }}; font-size: 0.92rem;">
                                                {{ $dept->Nazwa }}
                                                @if($isExpired)
                                                    <span style="font-size: 0.72rem; color: #ef4444; font-weight: 700;">(Wygasły)</span>
                                                @elseif($isFuture)
                                                    <span style="font-size: 0.72rem; color: #f59e0b; font-weight: 700;">(Przyszły)</span>
                                                @else
                                                    <span style="font-size: 0.72rem; color: var(--success); font-weight: 700;">(Aktywny)</span>
                                                @endif
                                            </div>
                                            @if($dept->tags && $dept->tags->isNotEmpty())
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.35rem;">
                                                    @foreach($dept->tags as $tag)
                                                        <span style="background: rgba(99, 102, 241, 0.12); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); padding: 0.1rem 0.45rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600;">
                                                            #{{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                            od: <strong style="color: var(--text-main);">{{ $dept->pivot->od ?: '∞' }}</strong>
                                            &nbsp;·&nbsp;
                                            do: <strong style="color: {{ $isExpired ? '#ef4444' : 'var(--text-main)' }};">{{ $dept->pivot->do ?: '∞' }}</strong>
                                        </div>
                                    </div>
                                @empty
                                    <div style="color: var(--text-muted); font-size: 0.875rem; text-align: center; padding: 0.5rem;">
                                        Brak przypisanych wydziałów.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border); flex-wrap: wrap;">
                        <a href="{{ route('access.index', ['user_id' => $user->id]) }}"
                           class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                            🔑 Przejdź do uprawnień
                        </a>
                        <a href="{{ route('users.index') }}"
                           style="padding: 0.7rem 1.4rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); text-decoration: none; font-weight: 600;">
                            ← Powrót do listy
                        </a>
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="padding: 1.25rem;">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.85rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>👤</span> Historia zmian nazwy, e-mail i wydziałów
                    </h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Data</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Pole</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Stara wartość</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Nowa wartość</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historyNameEmail as $log)
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 0.5rem 0.25rem; white-space: nowrap; color: var(--text-muted);" title="Zmieniający: {{ $log->editor->name ?? 'System' }}">
                                            {{ $log->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; font-weight: 500;">
                                            {{ $log->getFriendlyFieldName() }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--text-muted); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->old_value }}">
                                            {{ $log->old_value }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--primary); font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->new_value }}">
                                            {{ $log->new_value }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                                            Brak historii zmian nazwy, e-mail i wydziałów.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
<style>
    .show-grid {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 2rem;
        align-items: start;
        max-width: 1200px;
    }
    @media (max-width: 1024px) {
        .show-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
