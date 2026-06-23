<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container">
        <div class="header">
            <h1>Witaj ponownie</h1>
            <p>Zaloguj się, aby kontynuować</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <div class="form-group">
                <label for="email">Adres E-mail</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="jan@kowalski.pl" required>
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                <a href="{{ route('password.request') }}" class="forgot-password">Zapomniałeś hasła?</a>
            </div>

            <button type="submit" class="btn-submit">Zaloguj się</button>
        </form>

        <div class="footer">
            Nie masz jeszcze konta? <a href="{{ route('register') ?? '#' }}">Zarejestruj się</a>
        </div>
    </div>
</body>
</html>
