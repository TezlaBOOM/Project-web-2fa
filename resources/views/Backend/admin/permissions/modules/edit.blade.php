@extends('Backend.layouts.app')
@section('title', 'Edytuj Moduł')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Edytuj moduł: {{ $module->nazwa }}</h1>
            </div>
            <a href="{{ route('modules.index') }}" style="color: var(--text-muted); text-decoration: none;">← Powrót do listy</a>
        </div>

        <div style="max-width: 600px;">
            <div class="card" style="padding: 2rem;">
                <form action="{{ route('modules.update', $module->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="nazwa" class="form-label">Nazwa Modułu <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="nazwa" id="nazwa" class="form-control" value="{{ old('nazwa', $module->nazwa) }}" required>
                        @error('nazwa') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="parent_id" class="form-label">Kategoria Nadrzędna</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">-- Brak (Kategoria Główna) --</option>
                            @foreach($allModules as $mod)
                                @include('Backend.admin.permissions.modules._option', ['module' => $mod, 'depth' => 0, 'selectedId' => old('parent_id', $module->parent_id), 'disabledId' => $module->id])
                            @endforeach
                        </select>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Wybierz moduł nadrzędny lub pozostaw puste, by utworzyć kategorię główną. Moduł nie może być swoim własnym rodzicem.</p>
                        @error('parent_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
