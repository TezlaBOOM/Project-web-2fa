@extends('Backend.layouts.app')
@section('title', 'Zarządzanie Operacjami')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Operacje Systemowe</h1>
                <p>Zarządzaj operacjami</p>
            </div>
            <div>
                <a href="{{ route('operations.create') }}" class="btn-primary" style="text-decoration: none; padding: 0.5rem 1rem;">
                    + Dodaj Operację
                </a>
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
                        <th style="padding: 0.75rem;">ID</th>
                        <th style="padding: 0.75rem;">Nazwa Operacji</th>
                        <th style="padding: 0.75rem; text-align: right;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operations as $operation)
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 0.75rem;">{{ $operation->id }}</td>
                            <td style="padding: 0.75rem; font-weight: 500;">
                                {{ $operation->nazwa }}
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <a href="{{ route('operations.edit', $operation->id) }}" style="color: var(--primary); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;">Edytuj</a>
                                <form action="{{ route('operations.destroy', $operation->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Czy na pewno chcesz usunąć tę operację?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem; padding: 0;">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak operacji.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
