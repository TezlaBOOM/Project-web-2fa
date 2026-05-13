<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weryfikacja 2FA - Zaloguj</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
</head>
<body class="auth-page">

    <div class="auth-container">
        <!-- Lewa strona - Formularz -->
        <div class="auth-form-section">
            <div class="auth-form-wrapper">
                <div class="auth-header">
                    <div class="auth-logo">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>SecureApp</span>
                    </div>
                    <h1>Weryfikacja dwuetapowa</h1>
                    <p>Wysłaliśmy 6-cyfrowy kod na Twój adres e-mail. Wprowadź go poniżej, aby kontynuować.</p>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.2fa.verify') }}" class="auth-form">
                    @csrf
                    <div class="form-group">
                        <label for="two_factor_code">Kod weryfikacyjny</label>
                        <input type="text" id="two_factor_code" name="two_factor_code" placeholder="123456" required autofocus autocomplete="off" maxlength="6" style="text-align: center; letter-spacing: 0.5rem; font-size: 1.25rem; font-weight: 600;">
                        @error('two_factor_code')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary btn-block">Zweryfikuj</button>
                </form>

                <div class="auth-footer" style="margin-top: 1.5rem;">
                    <a href="{{ route('login') }}" class="auth-link">Powrót do logowania</a>
                </div>
            </div>
        </div>

        <!-- Prawa strona - Grafika -->
        <div class="auth-image-section">
            <div class="auth-image-content">
                <h2>Dodatkowa ochrona</h2>
                <p>Uwierzytelnianie dwuskładnikowe (2FA) znacząco zwiększa bezpieczeństwo Twojego konta przed nieautoryzowanym dostępem.</p>
            </div>
            <div class="auth-image-overlay"></div>
        </div>
    </div>

</body>
</html>
