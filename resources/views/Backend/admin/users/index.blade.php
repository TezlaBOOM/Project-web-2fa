@extends('Backend.layouts.app')
@section('title', 'Użytkownicy - Admin')

@section('content')
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">Moja Aplikacja</div>
        
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link">
                Panel Główny
            </a>
            <a href="{{ route('users.index') }}" class="nav-link active">
                Użytkownicy
            </a>
            <a href="{{ route('settings') }}" class="nav-link">
                Ustawienia
            </a>
        </nav>

        <div class="mt-auto">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Wyloguj się</button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Użytkownicy</h1>
                <p>Zarządzaj użytkownikami w systemie.</p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Administrator
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                ✓ {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Lista użytkowników</span>
                <a href="{{ route('users.create') }}" class="btn-add-user">
                    <span style="font-size: 1.1rem; line-height: 1;">＋</span> Dodaj użytkownika
                </a>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">ID</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Nazwa</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Email</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Rola</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Zarejestrowano</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted); text-align: right;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;"
                                    onmouseover="this.style.background='rgba(99,102,241,0.04)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">{{ $user->id }}</td>
                                    <td style="padding: 1rem; color: var(--text-main); font-weight: 500;">{{ $user->name }}</td>
                                    <td style="padding: 1rem; color: var(--text-muted);">{{ $user->email }}</td>
                                    <td style="padding: 1rem;">
                                        <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                            {{ ucfirst($user->role ?? 'Brak') }}
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                    <td style="padding: 1rem; text-align: right;">
                                        <a href="{{ route('users.edit', $user->id) }}" class="btn-table-action">
                                            ✎ Edytuj
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">Brak użytkowników do wyświetlenia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection
