<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetowanie hasła</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container">
        <div class="header">
            <h1>Zapomniałeś hasła?</h1>
            <p>Podaj swój adres e-mail, a wyślemy Ci link do resetowania hasła.</p>
        </div>

        @if (session('success'))
            <div class="alert-success">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <div class="form-group">
                <label for="email">Adres E-mail</label>
                <input type="email"
                       id="email"
                       name="email"
                       class="form-control"
                       placeholder="jan@kowalski.pl"
                       value="{{ old('email') }}"
                       required
                       autofocus>
            </div>

            <button type="submit" class="btn-submit">Wyślij link resetujący</button>
        </form>

        <div class="footer">
            <a href="{{ route('login') }}">← Powrót do logowania</a>
        </div>
    </div>

    <style>
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 0.875rem 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</body>
</html>
