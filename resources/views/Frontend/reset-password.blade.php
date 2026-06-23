<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowe hasło</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container">
        <div class="header">
            <h1>Nowe hasło</h1>
            <p>Wprowadź nowe hasło do swojego konta.</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf

            {{-- Token i email przekazywane jako ukryte pola --}}
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">Nowe hasło</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       placeholder="minimum 8 znaków"
                       required
                       autofocus
                       autocomplete="new-password">
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Potwierdź nowe hasło</label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control"
                       placeholder="powtórz hasło"
                       required
                       autocomplete="new-password">
            </div>

            <button type="submit" class="btn-submit">Ustaw nowe hasło</button>
        </form>

        <div class="footer">
            <a href="{{ route('login') }}">← Powrót do logowania</a>
        </div>
    </div>

    <style>
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
        .field-error {
            color: #ef4444;
            font-size: 0.8rem;
            margin-top: 0.4rem;
        }
    </style>
</body>
</html>
