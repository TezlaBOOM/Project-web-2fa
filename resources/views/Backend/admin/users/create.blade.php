@extends('Backend.layouts.app')
@section('title', 'Nowy użytkownik - Admin')

@section('content')
    @include('Backend.admin._sidebar')


    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Nowy użytkownik</h1>
                <p>Wypełnij formularz, aby dodać nowego użytkownika do systemu.</p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;"
                   onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Powrót do listy
                </a>
                <div class="status-badge">
                    Zalogowano jako Administrator
                </div>
            </div>
        </div>

        <div style="max-width: 640px;">
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
                <form action="{{ route('users.store') }}" method="POST" id="create-user-form">
                    @csrf

                    <!-- Imię i nazwisko -->
                    <div class="form-group">
                        <label for="name" class="form-label">Imię i nazwisko <span style="color: var(--danger);">*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control @error('name') form-control-error @enderror"
                            value="{{ old('name') }}"
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
                            value="{{ old('email') }}"
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
                        <select id="role" name="role" class="form-control @error('role') form-control-error @enderror">
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>— wybierz rolę —</option>
                            @foreach(['admin' => 'Administrator', 'mod' => 'Moderator', 'user' => 'Użytkownik', 'none' => 'Brak roli'] as $value => $label)
                                <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
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
                                        {{ in_array($dept->ID_Departament, old('departments', [])) ? 'checked' : '' }}
                                        style="width: 1.1rem; height: 1.1rem; accent-color: var(--primary); cursor: pointer;">
                                    <span>{{ $dept->Nazwa }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('departments')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Hasło -->
                    <div class="form-group">
                        <label for="password" class="form-label">Hasło <span style="color: var(--danger);">*</span></label>
                        <div style="position: relative;">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') form-control-error @enderror"
                                placeholder="min. 8 znaków"
                                autocomplete="new-password"
                                style="padding-right: 3rem;"
                            >
                            <button type="button" id="toggle-password"
                                style="position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 0;"
                                title="Pokaż/ukryj hasło">
                                👁
                            </button>
                        </div>
                        @error('password')
                            <p class="form-error-msg">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Potwierdzenie hasła -->
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Powtórz hasło <span style="color: var(--danger);">*</span></label>
                        <div style="position: relative;">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                                placeholder="powtórz hasło"
                                autocomplete="new-password"
                                style="padding-right: 3rem;"
                            >
                            <button type="button" id="toggle-password-confirm"
                                style="position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1; padding: 0;"
                                title="Pokaż/ukryj hasło">
                                👁
                            </button>
                        </div>
                    </div>

                    <!-- Przyciski akcji -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn-primary" id="submit-btn" style="flex: 1;">
                            Utwórz użytkownika
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
    // Toggle password visibility
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
