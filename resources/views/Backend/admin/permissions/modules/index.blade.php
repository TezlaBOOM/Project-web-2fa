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

        {{-- Filtry --}}
        <form method="GET" action="{{ route('modules.index') }}" id="search-form">
            <div style="display: flex; gap: 0.65rem; margin-bottom: 1.25rem; align-items: center; flex-wrap: wrap;">
                {{-- Wyszukiwarka --}}
                <div style="position: relative; flex: 1; min-width: 200px; max-width: 340px;">
                    <span style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem;">🔍</span>
                    <input type="text" name="search" id="search-input" class="form-control"
                           value="{{ $search ?? '' }}" placeholder="Szukaj modułów..."
                           style="padding-left: 2.2rem;" autocomplete="off">
                </div>

                {{-- Wyczyść --}}
                @if(!empty($search))
                    <a href="{{ route('modules.index') }}"
                       style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                        ✕ Wyczyść
                    </a>
                @endif
            </div>
        </form>

        <div class="card" style="padding: 1rem; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                        <th style="padding: 0.75rem;">Struktura Kategori</th>
                        <th style="padding: 0.75rem; text-align: right;">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($search))
                        @forelse($modules as $module)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 0.75rem; font-weight: 500;">
                                    {{ $module->full_path }}
                                </td>
                                <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                                    @if($module->getDepth() < 4)
                                        <a href="{{ route('modules.create', ['parent_id' => $module->id]) }}" style="color: var(--success); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;" title="Dodaj podkategorię pod tym modułem">+ Podkategoria</a>
                                    @endif
                                    <a href="{{ route('modules.edit', $module->id) }}" style="color: var(--primary); text-decoration: none; margin-right: 1rem; font-size: 0.85rem;">Edytuj</a>
                                    <form action="{{ route('modules.destroy', $module->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Czy na pewno chcesz usunąć ten moduł? Usunięcie modułu usunie również wszystkie jego podkategorie!');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 0.85rem; padding: 0;">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak pasujących modułów.</td>
                            </tr>
                        @endforelse
                    @else
                        @forelse($modules as $module)
                            @include('Backend.admin.permissions.modules._row', ['module' => $module, 'depth' => 0])
                        @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">Brak modułów.</td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('search-input');
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                document.getElementById('search-form').submit();
            }, 400);
        });
    }
</script>
@endpush
