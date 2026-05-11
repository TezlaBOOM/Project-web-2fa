<aside class="sidebar">
    <div class="logo">Moja Aplikacja</div>

    <nav>
        {{-- Panel Główny --}}
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Panel Główny
        </a>

        {{-- Użytkownicy (podmenu) --}}
        <div class="nav-group {{ request()->routeIs('users.*') || request()->routeIs('departments.*') ? 'open' : '' }}" id="nav-group-users">
            <div class="nav-group-header {{ request()->routeIs('users.*') || request()->routeIs('departments.*') ? 'active' : '' }}"
                 onclick="toggleNavGroup('nav-group-users')">
                <span>Użytkownicy</span>
                <span class="nav-group-arrow">▼</span>
            </div>
            <div class="nav-submenu">
                <a href="{{ route('users.index') }}"
                   class="nav-submenu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    Lista użytkowników
                </a>
                <a href="{{ route('departments.index') }}"
                   class="nav-submenu-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                    Lista wydziałów
                </a>
            </div>
        </div>



        {{-- Ustawienia --}}
        <a href="{{ route('settings') }}"
           class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}">
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

@push('scripts')
<script>
    function toggleNavGroup(id) {
        const group = document.getElementById(id);
        group.classList.toggle('open');
    }
</script>
@endpush
