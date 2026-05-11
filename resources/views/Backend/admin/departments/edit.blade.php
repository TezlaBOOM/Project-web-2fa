@extends('Backend.layouts.app')
@section('title', 'Edycja wydziału — {{ $department->Nazwa }}')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Edytuj wydział</h1>
                <p>Modyfikujesz wydział: <strong style="color: var(--text-main);">{{ $department->Nazwa }}</strong></p>
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
                <form action="{{ route('departments.update', $department->ID_Departament) }}" method="POST" id="edit-department-form">
                    @csrf
                    @method('PUT')

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
                            value="{{ old('Nazwa', $department->Nazwa) }}"
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
                        >{{ old('Description', $department->Description) }}</textarea>
                        @error('Description')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Przyciski akcji -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn-primary" id="submit-btn" style="flex: 1;">
                            Zapisz zmiany
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

            <!-- Info card -->
            <div style="margin-top: 1.25rem; padding: 1rem 1.25rem; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.15); border-radius: 12px; font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: flex-start; gap: 0.6rem;">
                <span style="font-size: 1rem;">ℹ️</span>
                <span>ID wydziału: <strong style="color: var(--text-main);">{{ $department->ID_Departament }}</strong> · Utworzono: <strong style="color: var(--text-main);">{{ $department->created_at->format('Y-m-d H:i') }}</strong></span>
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
