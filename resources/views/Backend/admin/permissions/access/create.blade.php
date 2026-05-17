@extends('Backend.layouts.app')
@section('title', 'Dodaj Uprawnienie')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Nadaj uprawnienie</h1>
            </div>
            <a href="{{ route('access.index') }}" style="color: var(--text-muted); text-decoration: none;">← Powrót do listy</a>
        </div>

        <div style="max-width: 600px;">
            <div class="card" style="padding: 2rem;">
                @if($errors->has('error'))
                    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                <form action="{{ route('access.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label for="user_id" class="form-label">Użytkownik <span style="color: var(--danger);">*</span></label>
                        <select name="user_id" id="user_id" class="form-control" required>
                            <option value="">-- Wybierz użytkownika --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="p_modul_id" class="form-label">Moduł <span style="color: var(--danger);">*</span></label>
                        <select name="p_modul_id" id="p_modul_id" class="form-control" required>
                            <option value="">-- Wybierz moduł --</option>
                            @foreach($modules as $module)
                                @include('Backend.admin.permissions.modules._option', ['module' => $module, 'depth' => 0, 'selectedId' => old('p_modul_id')])
                            @endforeach
                        </select>
                        @error('p_modul_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="p_operacje_id" class="form-label">Operacja <span style="color: var(--danger);">*</span></label>
                        <select name="p_operacje_id" id="p_operacje_id" class="form-control" required>
                            <option value="">-- Wybierz operację --</option>
                            @foreach($operations as $operation)
                                <option value="{{ $operation->id }}" {{ old('p_operacje_id') == $operation->id ? 'selected' : '' }}>{{ $operation->nazwa }}</option>
                            @endforeach
                        </select>
                        @error('p_operacje_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Dodaj uprawnienie</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
