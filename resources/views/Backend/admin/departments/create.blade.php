@extends('Backend.layouts.app')
@section('title', 'Nowy wydział - Admin')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Nowy wydział</h1>
                <p>Wypełnij formularz, aby dodać nowy wydział do systemu.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('departments.index') }}"
                   style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;"
                   onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Powrót do listy
                </a>
                <div class="status-badge">
                    Zalogowano jako Administrator
                </div>
            </div>
        </div>

        <div style="max-width: 640px;">
            @if ($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card" style="padding: 2rem;">
                <form action="{{ route('departments.store') }}" method="POST" id="create-department-form">
                    @csrf

                    <!-- Nazwa wydziału -->
                    <div class="form-group">
                        <label for="Nazwa" class="form-label">
                            Nazwa wydziału <span style="color: var(--danger);">*</span>
                        </label>
                        <input
                            type="text"
                            id="Nazwa"
                            name="Nazwa"
                            class="form-control @error('Nazwa') form-control-error @enderror"
                            value="{{ old('Nazwa') }}"
                            placeholder="np. Wydział Informatyki"
                            autocomplete="off"
                        >
                        @error('Nazwa')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Opis -->
                    <div class="form-group">
                        <label for="Description" class="form-label">Opis</label>
                        <textarea
                            id="Description"
                            name="Description"
                            class="form-control @error('Description') form-control-error @enderror"
                            rows="4"
                            placeholder="Krótki opis wydziału (opcjonalnie)…"
                            style="resize: vertical;"
                        >{{ old('Description') }}</textarea>
                        @error('Description')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tagi -->
                    <div class="form-group">
                        <label for="tags" class="form-label">Tagi</label>
                        <input
                            type="text"
                            id="tags"
                            name="tags"
                            class="form-control @error('tags') form-control-error @enderror"
                            value="{{ old('tags') }}"
                            placeholder="np. IT, Biuro, Centrala (oddziel przecinkami)"
                            autocomplete="off"
                        >
                        @error('tags')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
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

                    <!-- Przyciski akcji -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn-primary" id="submit-btn" style="flex: 1;">
                            Utwórz wydział
                        </button>
                        <a href="{{ route('departments.index') }}"
                           style="flex: 1; text-align: center; padding: 0.75rem 1.5rem; border: 1px solid var(--border); border-radius: 8px; color: var(--text-muted); text-decoration: none; font-weight: 600; transition: all 0.2s;"
                           onmouseover="this.style.borderColor='var(--text-muted)'; this.style.color='var(--text-main)'"
                           onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
                            Anuluj
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection

@push('styles')
<style>
    .form-control-error {
        border-color: var(--danger) !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }

    .form-error-msg {
        margin-top: 0.4rem;
        font-size: 0.8rem;
        color: var(--danger);
    }

    #submit-btn:active {
        transform: scale(0.98);
    }

    textarea.form-control {
        font-family: inherit;
        line-height: 1.6;
    }
</style>
@endpush

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
