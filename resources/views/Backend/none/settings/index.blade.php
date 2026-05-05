@extends('Backend.layouts.app')
@section('title', 'Ustawienia - Brak Uprawnień')

@section('content')
    <!-- Main Content -->
    <main class="main-content no-sidebar">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Ustawienia konta</h1>
                <p>Zarządzaj swoim kontem i zabezpieczeniami.</p>
            </div>
            
            <div class="header-actions">
                <a href="{{ route('dashboard') }}" class="btn-primary" style="text-decoration: none; display: inline-block;">Wróć do panelu</a>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout">Wyloguj się</button>
                </form>
            </div>
        </div>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header">
                Zmiana hasła
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

                <form action="{{ route('settings.password') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="current_password" class="form-label">Obecne hasło</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nowe hasło</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password_confirmation" class="form-label">Potwierdź nowe hasło</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn-primary">Zmień hasło</button>
                </form>
            </div>
        </div>
    </main>
@endsection
