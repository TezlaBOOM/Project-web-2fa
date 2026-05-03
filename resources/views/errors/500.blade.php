<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Błąd 500 - Wewnętrzny błąd serwera</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container" style="text-align: center;">
        <div class="header">
            <h1 style="font-size: 4rem; margin-bottom: 0;">500</h1>
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-main);">Wewnętrzny błąd serwera</h2>
            <p>Ups, coś poszło nie tak po naszej stronie. Nasi inżynierowie już nad tym pracują.</p>
        </div>

        <a href="{{ url('/') }}" class="btn-submit" style="display: inline-block; text-decoration: none; max-width: 250px; margin: 0 auto;">Spróbuj ponownie</a>
    </div>
</body>
</html>
