<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Błąd 403 - Brak dostępu</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="auth-container" style="text-align: center;">
        <div class="header">
            <h1 style="font-size: 4rem; margin-bottom: 0;">403</h1>
            <h2 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-main);">Brak dostępu</h2>
            <p>Przepraszamy, ale nie masz wystarczających uprawnień, aby wyświetlić tę stronę.</p>
        </div>

        <a href="{{ url('/') }}" class="btn-submit" style="display: inline-block; text-decoration: none; max-width: 250px; margin: 0 auto;">Strona główna</a>
    </div>
</body>
</html>
