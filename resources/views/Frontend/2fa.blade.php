<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weryfikacja 2FA - Zaloguj</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container">
        <div class="header">
            <h1>Weryfikacja dwuetapowa</h1>
            <p>Wysłaliśmy 6-cyfrowy kod na Twój adres e-mail. Wprowadź go poniżej.</p>
        </div>

        @if (session('error'))
            <div class="alert alert-danger" style="color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.2fa.verify') }}">
            @csrf
            <div class="form-group">
                <label for="two_factor_code">Kod weryfikacyjny</label>
                <input type="text" id="two_factor_code" name="two_factor_code" class="form-control" placeholder="123456" required autofocus autocomplete="off" maxlength="6" style="text-align: center; letter-spacing: 0.5rem; font-size: 1.25rem; font-weight: 600;">
                @error('two_factor_code')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.5rem;">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-submit">Zweryfikuj kod</button>
        </form>

        <div class="footer">
            <a href="{{ route('login') }}">Powrót do logowania</a>
        </div>
    </div>
</body>
</html>
