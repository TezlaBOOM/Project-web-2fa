@extends('Backend.layouts.app')
@section('title', 'Dodaj Moduł')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dodaj nowy moduł</h1>
            </div>
            <a href="{{ route('modules.index') }}" style="color: var(--text-muted); text-decoration: none;">← Powrót do listy</a>
        </div>

        <div style="max-width: 600px;">
            <div class="card" style="padding: 2rem;">
                <form action="{{ route('modules.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label for="nazwa" class="form-label">Nazwa Modułu <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="nazwa" id="nazwa" class="form-control" value="{{ old('nazwa') }}" required>
                        @error('nazwa') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="parent_id" class="form-label">Kategoria Nadrzędna</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">-- Brak (Kategoria Główna) --</option>
                            @foreach($allModules as $mod)
                                @include('Backend.admin.permissions.modules._option', ['module' => $mod, 'depth' => 0, 'selectedId' => old('parent_id', $parent_id)])
                            @endforeach
                        </select>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Wybierz moduł nadrzędny lub pozostaw puste, by utworzyć kategorię główną. Maksymalne zagnieżdżenie to 5 poziomów.</p>
                        @error('parent_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tags" class="form-label">Tagi</label>
                        <input
                            type="text"
                            id="tags"
                            name="tags"
                            class="form-control"
                            value="{{ old('tags') }}"
                            placeholder="np. Finanse, ERP, Krytyczny (oddziel przecinkami)"
                            autocomplete="off"
                        >
                        @error('tags') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        @if(isset($allTags) && $allTags->isNotEmpty())
                            <div style="margin-top: 0.6rem; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); margin-right: 0.25rem;">Istniejące tagi:</span>
                                @foreach($allTags as $tag)
                                    <button type="button" onclick="toggleTagChip('{{ addslashes($tag->name) }}')"
                                            style="background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.25); padding: 0.15rem 0.55rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; cursor: pointer;">
                                        + #{{ $tag->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Dodaj moduł</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    function toggleTagChip(tagName) {
        const input = document.getElementById('tags');
        if (!input) return;
        const parts = input.value.split(/[,;]+/).map(s => s.trim()).filter(Boolean);
        const idx = parts.findIndex(p => p.toLowerCase() === tagName.toLowerCase());
        if (idx === -1) {
            parts.push(tagName);
        } else {
            parts.splice(idx, 1);
        }
        input.value = parts.join(', ');
    }
</script>
@endpush
