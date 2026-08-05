@extends('Backend.layouts.app')
@section('title', 'Edycja użytkownika — {{ $user->name }}')

@section('content')
    @include('Backend.admin._sidebar')


    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Edytuj użytkownika</h1>
                <p>Modyfikujesz konto: <strong style="color: var(--text-main);">{{ $user->name }}</strong></p>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('users.index') }}"
                   style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;"
                   onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Powrót do listy
                </a>
                <div class="status-badge">
                    Zalogowano jako Administrator
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 420px; gap: 2rem; align-items: start; max-width: 1200px;">
            <div style="min-width: 0;">

                <!-- Validation errors -->
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
                    <form action="{{ route('users.update', $user->id) }}" method="POST" id="edit-user-form">
                        @csrf
                        @method('PUT')

                        <!-- Imię i nazwisko -->
                        <div class="form-group">
                            <label for="name" class="form-label">Imię i nazwisko <span style="color: var(--danger);">*</span></label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control @error('name') form-control-error @enderror"
                                value="{{ old('name', $user->name) }}"
                                placeholder="np. Jan Kowalski"
                                autocomplete="name"
                            >
                            @error('name')
                                <p class="form-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">Adres e-mail <span style="color: var(--danger);">*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control @error('email') form-control-error @enderror"
                                value="{{ old('email', $user->email) }}"
                                placeholder="np. jan@example.com"
                                autocomplete="email"
                            >
                            @error('email')
                                <p class="form-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Rola -->
                        <div class="form-group">
                            <label for="role" class="form-label">Rola <span style="color: var(--danger);">*</span></label>
                            <select id="role" name="role" class="form-control @error('role') form-control-error @enderror"
                                @if($user->id === auth()->id()) disabled @endif>
                                @foreach(['admin' => 'Administrator', 'mod' => 'Moderator', 'user' => 'Użytkownik', 'none' => 'Brak roli'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if($user->id === auth()->id())
                                <input type="hidden" name="role" value="{{ $user->role }}">
                                <p style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--warning, #f59e0b); display: flex; align-items: center; gap: 0.4rem;">
                                    <span>🔒</span> Nie możesz zmienić swojej własnej roli.
                                </p>
                            @endif
                            @error('role')
                                <p class="form-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Wydziały -->
                        <div class="form-group">
                            <label class="form-label">Wydziały</label>
                            <div class="checkbox-list @error('departments') form-control-error @enderror" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; max-height: 200px; overflow-y: auto;">
                                @foreach($departments as $dept)
                                    <label style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; cursor: pointer; color: var(--text-main);">
                                        <input type="checkbox" name="departments[]" value="{{ $dept->ID_Departament }}" 
                                            {{ in_array($dept->ID_Departament, old('departments', $user->departments->pluck('ID_Departament')->toArray())) ? 'checked' : '' }}
                                            style="width: 1.1rem; height: 1.1rem; accent-color: var(--primary); cursor: pointer;">
                                        <span>{{ $dept->Nazwa }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('departments')
                                <p class="form-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 2FA (Uwierzytelnianie dwuetapowe) -->
                        <div class="form-group" style="margin-top: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="two_factor_enabled" id="two_factor_enabled" value="1" 
                                {{ old('two_factor_enabled', $user->two_factor_enabled) ? 'checked' : '' }}
                                style="width: 1.1rem; height: 1.1rem; accent-color: var(--primary); cursor: pointer;">
                            <label for="two_factor_enabled" style="margin-bottom: 0; cursor: pointer; color: var(--text-main); font-weight: 500;">
                                Włącz uwierzytelnianie dwuetapowe (2FA) dla tego użytkownika
                            </label>
                        </div>

                        <!-- Aktywność Konta -->
                        <div class="form-group" style="margin-top: 1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_active" id="is_active" value="1" 
                                {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                @if($user->id === auth()->id()) disabled @endif
                                style="width: 1.1rem; height: 1.1rem; accent-color: var(--primary); cursor: pointer;">
                            <label for="is_active" style="margin-bottom: 0; cursor: pointer; color: var(--text-main); font-weight: 500;">
                                Konto jest aktywne
                            </label>
                            @if($user->id === auth()->id())
                                <input type="hidden" name="is_active" value="{{ $user->is_active }}">
                            @endif
                        </div>

                        <!-- Divider -->
                        <div style="border-top: 1px solid var(--border); margin: 1.75rem 0 1.5rem; position: relative;">
                            <span style="position: absolute; top: -0.65rem; left: 0; background: var(--surface); padding-right: 0.75rem; font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">
                                Zmiana hasła (opcjonalna)
                            </span>
                        </div>

                        <!-- Nowe hasło -->
                        <div class="form-group">
                            <label for="password" class="form-label">Nowe hasło</label>
                            <div style="position: relative;">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control @error('password') form-control-error @enderror"
                                    placeholder="pozostaw puste, aby nie zmieniać"
                                    autocomplete="new-password"
                                    style="padding-right: 3rem;"
                                >
                                <button type="button" id="toggle-password"
                                    style="position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 0;"
                                    title="Pokaż/ukryj hasło">👁</button>
                            </div>
                            @error('password')
                                <p class="form-error-msg">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Potwierdzenie hasła -->
                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Powtórz nowe hasło</label>
                            <div style="position: relative;">
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="form-control"
                                    placeholder="powtórz nowe hasło"
                                    autocomplete="new-password"
                                    style="padding-right: 3rem;"
                                >
                                <button type="button" id="toggle-password-confirm"
                                    style="position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 0;"
                                    title="Pokaż/ukryj hasło">👁</button>
                            </div>
                        </div>

                        <!-- Przyciski akcji -->
                        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                            <button type="submit" class="btn-primary" id="submit-btn" style="flex: 1;">
                                Zapisz zmiany
                            </button>
                            <a href="{{ route('users.index') }}"
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
                    <span>Pole hasła jest opcjonalne — zostaw puste, jeśli nie chcesz go zmieniać. Hasło musi mieć minimum 8 znaków.</span>
                </div>
            </div>

            <!-- Right Column: History -->
            <div style="display: grid; gap: 1.25rem;">
                <!-- Tabela 1: Zmiany nazwy i email -->
                <div class="card" style="padding: 1.25rem;">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.85rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>👤</span> Zmiany nazwy, e-mail i wydziałów
                    </h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Data</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Pole</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Stara wartość</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Nowa wartość</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historyNameEmail as $log)
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 0.5rem 0.25rem; white-space: nowrap; color: var(--text-muted);" title="Zmieniający: {{ $log->editor->name ?? 'System' }}">
                                            {{ $log->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; font-weight: 500;">
                                            {{ $log->getFriendlyFieldName() }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--text-muted); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->old_value }}">
                                            {{ $log->old_value }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--primary); font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->new_value }}">
                                            {{ $log->new_value }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                                            Brak historii zmian nazwy, e-mail i wydziałów.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabela 2: Pozostałe operacje -->
                <div class="card" style="padding: 1.25rem;">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.85rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                        <span>⚙️</span> Pozostałe operacje użytkownika
                    </h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Data</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Pole/Operacja</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Stara wartość</th>
                                    <th style="padding: 0.4rem 0.25rem; font-weight: 500;">Nowa wartość</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historyOther as $log)
                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                        <td style="padding: 0.5rem 0.25rem; white-space: nowrap; color: var(--text-muted);" title="Zmieniający: {{ $log->editor->name ?? 'System' }}">
                                            {{ $log->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; font-weight: 500;">
                                            {{ $log->getFriendlyFieldName() }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--text-muted); max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->old_value }}">
                                            {{ $log->old_value }}
                                        </td>
                                        <td style="padding: 0.5rem 0.25rem; color: var(--primary); font-weight: 500; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $log->new_value }}">
                                            {{ $log->new_value }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                                            Brak innych zarejestrowanych zmian.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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

    select.form-control option {
        background: var(--surface);
        color: var(--text-main);
    }

    #submit-btn:active {
        transform: scale(0.98);
    }
</style>
@endpush

@push('scripts')
<script>
    document.getElementById('toggle-password').addEventListener('click', function () {
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
    });

    document.getElementById('toggle-password-confirm').addEventListener('click', function () {
        const input = document.getElementById('password_confirmation');
        input.type = input.type === 'password' ? 'text' : 'password';
    });
</script>
@endpush
