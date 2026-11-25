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
                Zalogowano jako {{ auth()->user()->role === 'admin' ? 'Administrator' : (auth()->user()->role === 'view' ? 'Podgląd (View)' : (auth()->user()->role === 'support' ? 'Wsparcie (Support)' : ucfirst(auth()->user()->role))) }}
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
                <!-- Filtry wydziałów -->
                <form method="GET" action="{{ route('departments.index') }}" id="dept-filter-form" style="margin-bottom: 1.25rem; display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
                    @if($sortBy !== 'Nazwa')
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    @endif
                    @if($sortDir !== 'asc')
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    @endif

                    <div style="position: relative; flex: 1; min-width: 240px; max-width: 400px;">
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                               placeholder="Szukaj wydziału po nazwie, opisie lub tagu..."
                               class="form-control" style="width: 100%;">
                    </div>

                    <div style="min-width: 200px;">
                        <select name="tag_id" class="form-control" onchange="document.getElementById('dept-filter-form').submit()">
                            <option value="">Wszystkie tagi</option>
                            @foreach($tags ?? [] as $tag)
                                <option value="{{ $tag->id }}" {{ ($tagId ?? '') == $tag->id ? 'selected' : '' }}>
                                    #{{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">Filtruj</button>

                    @if(!empty($search) || !empty($tagId))
                        <a href="{{ route('departments.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.55rem 0.85rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                            ✕ Wyczyść
                        </a>
                    @endif
                </form>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'ID_Departament', 'sort_dir' => ($sortBy === 'ID_Departament' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        ID
                                        @if($sortBy === 'ID_Departament')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'Nazwa', 'sort_dir' => ($sortBy === 'Nazwa' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        Nazwa wydziału
                                        @if($sortBy === 'Nazwa')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Tagi</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Opis</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_dir' => ($sortBy === 'created_at' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        Utworzono
                                        @if($sortBy === 'created_at')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
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
                                    <td style="padding: 1rem;">
                                        @if($dept->tags && $dept->tags->isNotEmpty())
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                                @foreach($dept->tags as $tag)
                                                    <a href="{{ route('departments.index', array_filter(['tag_id' => $tag->id, 'search' => $search ?? null])) }}"
                                                       style="background: rgba(99, 102, 241, 0.12); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; text-decoration: none; white-space: nowrap;">
                                                        #{{ $tag->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
                                        @endif
                                    </td>
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
                                    <td colspan="{{ auth()->user()->role === 'admin' ? 6 : 5 }}"
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

@push('styles')
<style>
    .sort-header:hover {
        color: var(--primary) !important;
    }
</style>
@endpush
