@extends('Backend.layouts.app')
@section('title', 'Wydziały - Admin')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Wydziały</h1>
                <p>Zarządzaj wydziałami (departamentami) w systemie.</p>
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

        @if(session('error'))
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                ✗ {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Lista wydziałów</span>
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('departments.create') }}" class="btn-add-user">
                    <span style="font-size: 1.1rem; line-height: 1;">＋</span> Dodaj wydział
                </a>
                @endif
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">ID</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Nazwa wydziału</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Opis</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Utworzono</th>
                                @if(auth()->user()->role === 'admin')
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted); text-align: right;">Akcje</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $dept)
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;"
                                    onmouseover="this.style.background='rgba(99,102,241,0.04)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">{{ $dept->ID_Departament }}</td>
                                    <td style="padding: 1rem; color: var(--text-main); font-weight: 500;">{{ $dept->Nazwa }}</td>
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem; max-width: 320px;">
                                        {{ $dept->Description ? Str::limit($dept->Description, 80) : '—' }}
                                    </td>
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">{{ $dept->created_at->format('Y-m-d H:i') }}</td>
                                    @if(auth()->user()->role === 'admin')
                                    <td style="padding: 1rem; text-align: right; white-space: nowrap; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <a href="{{ route('departments.edit', $dept->ID_Departament) }}" class="btn-table-action">
                                            ✎ Edytuj
                                        </a>
                                        @if(strtolower($dept->Nazwa) !== 'all')
                                        <form action="{{ route('departments.destroy', $dept->ID_Departament) }}" method="POST"
                                              onsubmit="return confirm('Czy na pewno chcesz usunąć wydział „{{ $dept->Nazwa }}"?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-table-danger">🗑 Usuń</button>
                                        </form>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->role === 'admin' ? 5 : 4 }}"
                                        style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        Brak wydziałów do wyświetlenia.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection
