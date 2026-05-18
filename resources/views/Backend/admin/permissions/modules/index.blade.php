@extends('Backend.layouts.app')
@section('title', 'Zarządzanie Modułami')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Moduły Systemowe</h1>
                <p>Zarządzaj modułami i ich hierarchią</p>
            </div>
            <div>
                <a href="{{ route('modules.create') }}" class="btn-primary" style="text-decoration: none; padding: 0.5rem 1rem;">
                    + Dodaj Moduł
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                ⚠ {{ session('error') }}
            </div>
        @endif

        <div class="card" style="padding: 1rem; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                        <th style="padding: 0.75rem;">Struktura Kategori</th>
                        <th style="padding: 0.75rem; text-align: right;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modules as $module)
                        @include('Backend.admin.permissions.modules._row', ['module' => $module, 'depth' => 0])
                    @empty
                        <tr>
                            <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak modułów.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
@endsection
