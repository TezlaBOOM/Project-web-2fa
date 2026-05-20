<aside class="sidebar">
    <div class="logo">Moja Aplikacja</div>

    <nav>
        {{-- Panel Główny --}}
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            Panel Główny
        </a>

        {{-- Dokumenty --}}
        <a href="{{ route('documents.index') }}"
           class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">
            Dokumenty
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

        @if(auth()->user()->role === 'admin' || auth()->user()->role === 'mod')
            {{-- Uprawnienia (podmenu) --}}
            <div class="nav-group {{ request()->routeIs('modules.*') || request()->routeIs('operations.*') || request()->routeIs('access.*') ? 'open' : '' }}" id="nav-group-permissions">
                <div class="nav-group-header {{ request()->routeIs('modules.*') || request()->routeIs('operations.*') || request()->routeIs('access.*') ? 'active' : '' }}"
                     onclick="toggleNavGroup('nav-group-permissions')">
                    <span>Uprawnienia</span>
                    <span class="nav-group-arrow">▼</span>
                </div>
                <div class="nav-submenu">
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('modules.index') }}"
                           class="nav-submenu-link {{ request()->routeIs('modules.*') ? 'active' : '' }}">
                            Moduły
                        </a>
                        <a href="{{ route('operations.index') }}"
                           class="nav-submenu-link {{ request()->routeIs('operations.*') ? 'active' : '' }}">
                            Operacje
                        </a>
                    @endif
                    <a href="{{ route('access.index') }}"
                       class="nav-submenu-link {{ request()->routeIs('access.*') ? 'active' : '' }}">
                        Dostęp
                    </a>
                </div>
            </div>
        @endif

        {{-- Ustawienia (podmenu) --}}
        <div class="nav-group {{ request()->routeIs('settings*') ? 'open' : '' }}" id="nav-group-settings">
            <div class="nav-group-header {{ request()->routeIs('settings*') ? 'active' : '' }}"
                 onclick="toggleNavGroup('nav-group-settings')">
                <span>Ustawienia</span>
                <span class="nav-group-arrow">▼</span>
            </div>
            <div class="nav-submenu">
                <a href="{{ route('settings') }}"
                   class="nav-submenu-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                    Zmiana hasła
                </a>
                <a href="{{ route('settings.logon') }}"
                   class="nav-submenu-link {{ request()->routeIs('settings.logon') ? 'active' : '' }}">
                    Logowanie
                </a>
            </div>
        </div>
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
