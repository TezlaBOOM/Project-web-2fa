@extends('Backend.layouts.app')
@section('title', 'Logowanie i 2FA - Admin')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Ustawienia Logowania</h1>
                <p>Zarządzaj polityką haseł oraz uwierzytelnianiem dwuskładnikowym (2FA).</p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Administrator
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Konfiguracja Logowania i Zabezpieczeń
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('settings.logon.update') }}" method="POST">
                    @csrf
                    
                    <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Polityka Haseł</h3>
                    
                    <div class="form-group">
                        <label for="min_password_length" class="form-label">Minimalna długość hasła</label>
                        <input type="number" name="min_password_length" id="min_password_length" class="form-control" value="{{ old('min_password_length', $settings['min_password_length'] ?? 8) }}" required min="4">
                    </div>

                    <div class="form-group">
                        <label for="max_password_length" class="form-label">Maksymalna długość hasła</label>
                        <input type="number" name="max_password_length" id="max_password_length" class="form-control" value="{{ old('max_password_length', $settings['max_password_length'] ?? 32) }}" required>
                    </div>

                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <input type="checkbox" name="require_special_character" id="require_special_character" value="1" {{ old('require_special_character', $settings['require_special_character'] ?? 0) ? 'checked' : '' }}>
                        <label for="require_special_character" style="margin-bottom: 0;">Wymagaj znaku specjalnego w haśle</label>
                    </div>

                    <h3 style="margin-bottom: 1rem; margin-top: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Uwierzytelnianie Dwuskładnikowe (2FA)</h3>
                    
                    <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <input type="checkbox" name="enable_2fa" id="enable_2fa" value="1" {{ old('enable_2fa', $settings['enable_2fa'] ?? 0) ? 'checked' : '' }}>
                        <label for="enable_2fa" style="margin-bottom: 0; font-weight: 600; color: var(--primary);">Aktywuj 2FA (wysyłanie tokenów e-mail)</label>
                    </div>

                    <h3 style="margin-bottom: 1rem; margin-top: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Konfiguracja Serwera SMTP (dla 2FA)</h3>

                    <div class="form-group">
                        <label for="smtp_host" class="form-label">Host SMTP</label>
                        <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_port" class="form-label">Port SMTP</label>
                        <input type="text" name="smtp_port" id="smtp_port" class="form-control" value="{{ old('smtp_port', $settings['smtp_port'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_username" class="form-label">Nazwa użytkownika SMTP</label>
                        <input type="text" name="smtp_username" id="smtp_username" class="form-control" value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_password" class="form-label">Hasło SMTP</label>
                        <input type="password" name="smtp_password" id="smtp_password" class="form-control" value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_encryption" class="form-label">Szyfrowanie (np. tls, ssl)</label>
                        <input type="text" name="smtp_encryption" id="smtp_encryption" class="form-control" value="{{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_from_address" class="form-label">Adres e-mail nadawcy (From Address)</label>
                        <input type="email" name="smtp_from_address" id="smtp_from_address" class="form-control" value="{{ old('smtp_from_address', $settings['smtp_from_address'] ?? '') }}">
                    </div>

                    <div class="form-group">
                        <label for="smtp_from_name" class="form-label">Nazwa nadawcy (From Name)</label>
                        <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control" value="{{ old('smtp_from_name', $settings['smtp_from_name'] ?? '') }}">
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Zapisz ustawienia</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
