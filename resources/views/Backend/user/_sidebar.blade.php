<aside class="sidebar">
    <div class="logo">Moja Aplikacja</div>
    
    <nav>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Panel Główny
        </a>
        <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
            Dokumenty
        </a>
        <a href="#" class="nav-link">
            Profil
        </a>
        <a href="{{ route('settings') }}" class="nav-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
            Ustawienia
        </a>
    </nav>

    <div class="mt-auto">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">Wyloguj się</button>
        </form>
    </div>
</aside>
