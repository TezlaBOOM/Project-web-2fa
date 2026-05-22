@extends('Backend.layouts.app')
@section('title', 'Dodaj Uprawnienie')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                @if($preselectedUser)
                    <h1>Nadaj uprawnienie</h1>
                    <p>Użytkownik: <strong style="color: var(--primary);">{{ $preselectedUser->name }}</strong> &bull; {{ $preselectedUser->email }}</p>
                @else
                    <h1>Nadaj uprawnienie</h1>
                @endif
            </div>
            <a href="{{ route('access.index', $preselectedUser ? ['user_id' => $preselectedUser->id] : []) }}"
               style="color: var(--text-muted); text-decoration: none;">← Powrót</a>
        </div>

        <div style="max-width: 560px;">
            <div class="card" style="padding: 1.75rem 2rem;">
                @if($errors->has('error'))
                    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                        {{ $errors->first('error') }}
                    </div>
                @endif

                <form action="{{ route('access.store') }}" method="POST">
                    @csrf

                    {{-- Użytkownik: ukryty gdy znany z kontekstu, select gdy nieznany --}}
                    @if($preselectedUser)
                        <input type="hidden" name="user_id" value="{{ $preselectedUser->id }}">
                        <div style="background: rgba(99,102,241,0.07); border: 1px solid rgba(99,102,241,0.2); border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem; color: white; flex-shrink: 0;">
                                {{ strtoupper(substr($preselectedUser->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-color);">{{ $preselectedUser->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $preselectedUser->email }}</div>
                            </div>
                        </div>
                    @else
                        <div class="form-group">
                            <label for="user_id" class="form-label">Użytkownik <span style="color: var(--danger);">*</span></label>
                            <select name="user_id" id="user_id" class="form-control" required>
                                <option value="">-- Wybierz użytkownika --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            @error('user_id') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                    @endif

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

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="valid_from" class="form-label">Ważne od</label>
                            <input type="date" name="valid_from" id="valid_from" class="form-control" value="{{ old('valid_from') }}">
                            @error('valid_from') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="valid_to" class="form-label">Ważne do</label>
                            <input type="date" name="valid_to" id="valid_to" class="form-control" value="{{ old('valid_to') }}">
                            @error('valid_to') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn-primary">Dodaj uprawnienie</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
