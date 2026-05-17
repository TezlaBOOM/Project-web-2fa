@extends('Backend.layouts.app')
@section('title', 'Dostęp (Uprawnienia)')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dostęp Użytkowników</h1>
                <p>Lista przypisanych uprawnień (Moduły i Operacje)</p>
            </div>
            <div>
                @if($role === 'admin')
                    <a href="{{ route('access.create') }}" class="btn-primary" style="text-decoration: none; padding: 0.5rem 1rem;">
                        + Dodaj Uprawnienie
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        <div class="card" style="padding: 1rem; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                        <th style="padding: 0.75rem;">Użytkownik</th>
                        <th style="padding: 0.75rem;">Moduł</th>
                        <th style="padding: 0.75rem;">Operacja</th>
                        @if($role === 'admin')
                            <th style="padding: 0.75rem; text-align: right;">Akcje</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($accesses as $access)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem; font-weight: 500;">
                                {{ $access->user->name }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $access->user->email }}</div>
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $access->modul->nazwa ?? 'Brak' }}
                            </td>
                            <td style="padding: 0.75rem;">
                                {{ $access->operacja->nazwa ?? 'Brak' }}
                            </td>
                            @if($role === 'admin')
                                <td style="padding: 0.75rem; text-align: right;">
                                    <a href="{{ route('access.edit', $access->id) }}" style="color: var(--primary); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;">Edytuj</a>
                                    <form action="{{ route('access.destroy', $access->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Czy na pewno chcesz usunąć to uprawnienie?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem; padding: 0;">Usuń</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $role === 'admin' ? 4 : 3 }}" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak przypisanych uprawnień.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
