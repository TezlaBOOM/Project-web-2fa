@extends('Backend.layouts.app')
@section('title', 'Użytkownicy - Moderator')

@section('content')
    @include('Backend.mod._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Użytkownicy</h1>
                <p>Przeglądaj listę użytkowników w twoim wydziale.</p>
            </div>
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Moderator
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Lista użytkowników
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.85rem;">
                            <th style="padding: 0.85rem 1rem;">Użytkownik</th>
                            <th style="padding: 0.85rem 1rem;">Rola</th>
                            <th style="padding: 0.85rem 1rem;">Status</th>
                            <th style="padding: 0.85rem 1rem;">Zarejestrowano</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 0.85rem 1rem;">
                                    <div style="font-weight: 500; color: var(--text-color);">{{ $user->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $user->email }}</div>
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                        {{ ucfirst($user->role ?? 'Brak') }}
                                    </span>
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    @if($user->is_active)
                                        <span style="color: var(--success); font-size: 0.8rem;">● Aktywny</span>
                                    @else
                                        <span style="color: var(--danger); font-size: 0.8rem;">● Zablokowany</span>
                                    @endif
                                </td>
                                <td style="padding: 0.85rem 1rem; color: var(--text-muted); font-size: 0.85rem;">
                                    {{ $user->created_at->format('Y-m-d') }}
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: right;">
                                    <a href="{{ route('users.permissions', $user->id) }}" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; background: rgba(99,102,241,0.1); padding: 0.3rem 0.75rem; border-radius: 6px;">
                                        Uprawnienia
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">Brak użytkowników do wyświetlenia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
@endsection
