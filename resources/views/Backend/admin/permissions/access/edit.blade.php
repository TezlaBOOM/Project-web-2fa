@extends('Backend.layouts.app')
@section('title', 'Edytuj Uprawnienie')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Edytuj uprawnienie</h1>
            </div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('access.index', ['user_id' => $access->user_id]) }}"
               onclick="if(window.history.length > 1) { window.history.back(); return false; }"
               style="color: var(--text-muted); text-decoration: none;">← Powrót</a>
        </div>

        <div style="max-width: 600px;">
            <div class="card" style="padding: 2rem;">
                @if($errors->has('error'))
                    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                <form action="{{ route('access.update', $access->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label for="user_id" class="form-label">Użytkownik <span style="color: var(--danger);">*</span></label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <option value="">-- Wybierz użytkownika --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', $access->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="p_modul_id" class="form-label">Moduł <span style="color: var(--danger);">*</span></label>
                        <select name="p_modul_id" id="p_modul_id" class="form-control" required>
                            <option value="">-- Wybierz moduł --</option>
                            @foreach($modules as $module)
                                @include('Backend.admin.permissions.modules._option', ['module' => $module, 'depth' => 0, 'selectedId' => old('p_modul_id', $access->p_modul_id)])
                            @endforeach
                        </select>
                        @error('p_modul_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="p_operacje_id" class="form-label">Operacja <span style="color: var(--danger);">*</span></label>
                        <select name="p_operacje_id" id="p_operacje_id" class="form-control" required>
                            <option value="">-- Wybierz operację --</option>
                            @foreach($operations as $operation)
                                <option value="{{ $operation->id }}" {{ old('p_operacje_id', $access->p_operacje_id) == $operation->id ? 'selected' : '' }}>{{ $operation->nazwa }}</option>
                            @endforeach
                        </select>
                        @error('p_operacje_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="valid_from" class="form-label">Ważne od</label>
                            <input type="date" name="valid_from" id="valid_from" class="form-control" value="{{ old('valid_from', $access->valid_from ? $access->valid_from->format('Y-m-d') : '') }}">
                            @error('valid_from') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="valid_to" class="form-label">Ważne do</label>
                            <input type="date" name="valid_to" id="valid_to" class="form-control" value="{{ old('valid_to', $access->valid_to ? $access->valid_to->format('Y-m-d') : '') }}">
                            @error('valid_to') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Login -->
                    <div class="form-group">
                        <label for="login" class="form-label">Login</label>
                        <input type="text" name="login" id="login" class="form-control" value="{{ old('login', $access->login) }}" placeholder="np. jkowalski">
                        @error('login') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <!-- Uwagi -->
                    <div class="form-group">
                        <label for="uwagi" class="form-label">Uwagi</label>
                        <textarea name="uwagi" id="uwagi" class="form-control" placeholder="Dodatkowe informacje, uwagi..." rows="3">{{ old('uwagi', $access->uwagi) }}</textarea>
                        @error('uwagi') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center; margin-top: 2rem; flex-wrap: wrap;">
                        <button type="submit" class="btn-primary">Zapisz zmiany</button>
                        <a href="{{ route('access.create', ['duplicate_id' => $access->id]) }}"
                           class="btn-secondary"
                           style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1.2rem; border-radius: 6px; background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.9rem; font-weight: 500; border: 1px solid rgba(99,102,241,0.25); transition: background 0.15s;"
                           onmouseover="this.style.background='rgba(99,102,241,0.2)'"
                           onmouseout="this.style.background='rgba(99,102,241,0.1)'">
                            📋 Duplikuj
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
