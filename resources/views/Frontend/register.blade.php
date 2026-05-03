<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container">
        <div class="header">
            <h1>Dołącz do nas</h1>
            <p>Utwórz nowe konto, aby rozpocząć</p>
        </div>

        <form action="{{ route('register') ?? '#' }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Imię i nazwisko</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Jan Kowalski" required>
            </div>

            <div class="form-group">
                <label for="email">Adres E-mail</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="jan@kowalski.pl" required>
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Potwierdź hasło</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Zarejestruj się</button>
        </form>

        <div class="footer">
            Masz już konto? <a href="{{ route('login') ?? '#' }}">Zaloguj się</a>
        </div>
    </div>
</body>
</html>
