@extends('Backend.layouts.app')
@section('title', 'Dostęp (Uprawnienia)')

@section('content')
    @if(auth()->user()->role === 'admin')
        @include('Backend.admin._sidebar')
    @else
        @include('Backend.mod._sidebar')
    @endif

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dostęp Użytkowników</h1>
                <p>Zarządzaj uprawnieniami – {{ $users->total() }} {{ $users->total() === 1 ? 'użytkownik' : 'użytkowników' }}</p>
            </div>
            @if($role === 'admin')
                <a href="{{ route('access.create', $selectedUser ? ['user_id' => $selectedUser->id] : []) }}"
                   class="btn-primary" style="text-decoration: none; padding: 0.5rem 1.25rem; white-space: nowrap;">
                    + Dodaj Uprawnienie
                </a>
            @endif
        </div>

        @if(session('success'))
            <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Filtry --}}
        <form method="GET" action="{{ route('access.index') }}" id="search-form">
            @if($userId)
                <input type="hidden" name="user_id" value="{{ $userId }}">
            @endif
            <div style="display: flex; gap: 0.65rem; margin-bottom: 1.25rem; align-items: center; flex-wrap: wrap;">
                {{-- Wyszukiwarka --}}
                <div style="position: relative; flex: 1; min-width: 200px; max-width: 340px;">
                    <span style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem;">🔍</span>
                    <input type="text" name="search" id="search-input" class="form-control"
                           value="{{ $search }}" placeholder="Szukaj po nazwie lub e-mailu..."
                           style="padding-left: 2.2rem;" autocomplete="off">
                </div>

                {{-- Filtr wydziału --}}
                <div style="min-width: 190px;">
                    <select name="dept_id" id="dept-filter" class="form-control" onchange="document.getElementById('search-form').submit()">
                        <option value="">Wszystkie wydziały</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->ID_Departament }}" {{ $deptId == $dept->ID_Departament ? 'selected' : '' }}>
                                {{ $dept->Nazwa }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filtr statusu konta --}}
                <div style="min-width: 175px;">
                    <select name="user_status" id="user-status-filter" class="form-control" onchange="document.getElementById('search-form').submit()">
                        <option value="">Wszyscy użytkownicy</option>
                        <option value="active" {{ ($userStatus === 'active' || $userStatus === '1') ? 'selected' : '' }}>Tylko aktywni</option>
                        <option value="inactive" {{ ($userStatus === 'inactive' || $userStatus === '0') ? 'selected' : '' }}>Tylko nieaktywni</option>
                    </select>
                </div>

                {{-- Wyczyść --}}
                @if($search || $deptId || $userStatus)
                    <a href="{{ route('access.index', $userId ? ['user_id' => $userId] : []) }}"
                       style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                        ✕ Wyczyść filtry
                    </a>
                @endif
            </div>
        </form>

        <div style="display: grid; grid-template-columns: {{ $selectedUser ? '320px 1fr' : '1fr' }}; gap: 1.25rem; align-items: start;">

            {{-- ═══ LEWA KOLUMNA – lista użytkowników ═══ --}}
            <div>
                <div class="card" style="overflow: hidden; padding: 0;">
                    <div style="padding: 0.75rem 1.1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase;">Użytkownicy</span>
                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.15rem; font-weight: 500;" class="sort-header" title="Sortuj po nazwie">
                                Nazwa @if($sortBy === 'name') <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span> @else <span style="opacity: 0.4;">⇅</span> @endif
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_dir' => ($sortBy === 'email' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.15rem; font-weight: 500;" class="sort-header" title="Sortuj po emailu">
                                Email @if($sortBy === 'email') <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span> @else <span style="opacity: 0.4;">⇅</span> @endif
                            </a>
                        </div>
                        @if($deptId)
                            @php $deptName = $departments->firstWhere('ID_Departament', $deptId)?->Nazwa; @endphp
                            @if($deptName)
                                <span style="font-size: 0.73rem; background: rgba(99,102,241,0.12); color: var(--primary); padding: 0.15rem 0.55rem; border-radius: 999px;">{{ $deptName }}</span>
                            @endif
                        @endif
                    </div>

                    @forelse($users as $user)
                        @php $isSelected = $selectedUser && $selectedUser->id === $user->id; @endphp
                        <a href="{{ route('access.index', array_filter(['user_id' => $user->id, 'search' => $search, 'dept_id' => $deptId, 'user_status' => $userStatus, 'sort_by' => $sortBy, 'sort_dir' => $sortDir, 'access_sort_by' => $accessSortBy, 'access_sort_dir' => $accessSortDir])) }}"
                           style="display: flex; align-items: center; gap: 0.8rem; padding: 0.75rem 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.04); text-decoration: none; background: {{ $isSelected ? 'rgba(99,102,241,0.1)' : 'transparent' }}; transition: background 0.12s;"
                           class="user-row {{ $isSelected ? 'selected' : '' }}">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: {{ $isSelected ? 'var(--primary)' : 'rgba(99,102,241,0.18)' }}; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: {{ $isSelected ? 'white' : 'var(--primary)' }}; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 500; font-size: 0.875rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 0.3rem;">
                                    <span>{{ $user->name }}</span>
                                    @if(!$user->is_active)
                                        <span style="color: #ef4444; font-size: 0.65rem; font-weight: 600; background: rgba(239, 68, 68, 0.12); padding: 0.05rem 0.35rem; border-radius: 4px;">Nieaktywny</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
                                @if($user->departments && $user->departments->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.2rem; margin-top: 0.2rem;">
                                        @foreach($user->departments as $dept)
                                            @php
                                                $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                            @endphp
                                            <span title="{{ $dept->Nazwa }}@if($dept->pivot->od) (od: {{ $dept->pivot->od }})@endif @if($dept->pivot->do) (do: {{ $dept->pivot->do }})@endif @if($isExpired) - NIEAKTYWNY @endif"
                                                  style="background: {{ $isExpired ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.12)' }}; color: {{ $isExpired ? '#ef4444' : 'var(--success)' }}; font-size: 0.65rem; font-weight: 600; padding: 0.1rem 0.4rem; border-radius: 999px; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.2rem;">
                                                <span>{{ $dept->Nazwa }}</span>
                                                @if($dept->pivot->do)
                                                    <span style="color: {{ $isExpired ? '#ef4444' : 'inherit' }}; font-weight: {{ $isExpired ? '700' : '500' }}; font-size: 0.62rem;">
                                                        (do: {{ $dept->pivot->do }})
                                                    </span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div style="flex-shrink: 0;">
                                @if($user->p_accesses_count > 0)
                                    <span style="background: {{ $isSelected ? 'rgba(255,255,255,0.18)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isSelected ? 'white' : 'var(--primary)' }}; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.73rem; font-weight: 600;">{{ $user->p_accesses_count }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.72rem;">—</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                            @if($search || $deptId)
                                Brak wyników dla podanych filtrów.
                            @else
                                Brak użytkowników.
                            @endif
                        </div>
                    @endforelse

                    {{-- Paginacja --}}
                    @if($users->hasPages())
                        <div style="padding: 0.65rem 1.1rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border);">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">{{ $users->firstItem() }}–{{ $users->lastItem() }} / {{ $users->total() }}</span>
                            <div style="display: flex; gap: 0.3rem;">
                                @if($users->onFirstPage())
                                    <span style="padding: 0.25rem 0.6rem; border-radius: 5px; font-size: 0.75rem; color: var(--text-muted); opacity: 0.4;">←</span>
                                @else
                                    <a href="{{ $users->previousPageUrl() }}" style="padding: 0.25rem 0.6rem; border-radius: 5px; font-size: 0.75rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.1);">←</a>
                                @endif
                                @if($users->hasMorePages())
                                    <a href="{{ $users->nextPageUrl() }}" style="padding: 0.25rem 0.6rem; border-radius: 5px; font-size: 0.75rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.1);">→</a>
                                @else
                                    <span style="padding: 0.25rem 0.6rem; border-radius: 5px; font-size: 0.75rem; color: var(--text-muted); opacity: 0.4;">→</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══ PRAWA KOLUMNA – szczegóły / drzewko uprawnień ═══ --}}
            @if($selectedUser)
                <div>
                    {{-- Karta użytkownika --}}
                    <div class="card" style="padding: 1.1rem 1.35rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 0.9rem;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; color: white; flex-shrink: 0;">
                                    {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-color);">{{ $selectedUser->name }}</div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.1rem;">
                                        {{ $selectedUser->email }}
                                        &bull; <span style="color: var(--primary);">{{ ucfirst($selectedUser->role) }}</span>
                                    </div>
                                    @if($selectedUser->departments->isNotEmpty())
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.35rem; align-items: center;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Wydziały:</span>
                                            @foreach($selectedUser->departments as $dept)
                                                @php
                                                    $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                                @endphp
                                                <span title="{{ $dept->Nazwa }}@if($dept->pivot->od) (od: {{ $dept->pivot->od }})@endif @if($dept->pivot->do) (do: {{ $dept->pivot->do }})@endif @if($isExpired) - NIEAKTYWNY @endif"
                                                      style="background: {{ $isExpired ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.12)' }}; color: {{ $isExpired ? '#ef4444' : 'var(--success)' }}; border: 1px solid {{ $isExpired ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.2)' }}; padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.72rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <span>{{ $dept->Nazwa }}</span>
                                                    @if($dept->pivot->do)
                                                        <span style="color: {{ $isExpired ? '#ef4444' : 'inherit' }}; font-weight: {{ $isExpired ? '700' : '500' }}; font-size: 0.68rem;">
                                                            (do: {{ $dept->pivot->do }})
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if($role === 'admin')
                                <a href="{{ route('access.create', ['user_id' => $selectedUser->id]) }}"
                                   style="color: var(--primary); font-size: 0.82rem; text-decoration: none; background: rgba(99,102,241,0.1); padding: 0.35rem 0.8rem; border-radius: 6px; white-space: nowrap;">
                                    + Dodaj uprawnienie
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Pasek nagłówka uprawnień z sortowaniem --}}
                    @if($selectedAccesses && $selectedAccesses->count() > 0)
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                Przypisane uprawnienia ({{ $selectedAccesses->count() }})
                            </span>
                            <div style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.72rem; color: var(--text-muted);">
                                <span>Sortuj:</span>
                                <a href="{{ request()->fullUrlWithQuery(['access_sort_by' => 'module', 'access_sort_dir' => ($accessSortBy === 'module' && $accessSortDir === 'asc') ? 'desc' : 'asc']) }}"
                                   style="color: {{ $accessSortBy === 'module' ? 'var(--primary)' : 'inherit' }}; text-decoration: none; padding: 0.18rem 0.5rem; border-radius: 4px; background: {{ $accessSortBy === 'module' ? 'rgba(99,102,241,0.12)' : 'rgba(255,255,255,0.04)' }}; display: inline-flex; align-items: center; gap: 0.2rem; font-weight: 500;"
                                   class="sort-header" title="Sortuj po nazwie modułu">
                                    <span>Moduł</span>
                                    <span style="opacity: {{ $accessSortBy === 'module' ? '1' : '0.3' }}; font-size: 0.65rem;">{{ $accessSortBy === 'module' ? ($accessSortDir === 'asc' ? '▲' : '▼') : '▲' }}</span>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['access_sort_by' => 'date', 'access_sort_dir' => ($accessSortBy === 'date' && $accessSortDir === 'asc') ? 'desc' : 'asc']) }}"
                                   style="color: {{ $accessSortBy === 'date' ? 'var(--primary)' : 'inherit' }}; text-decoration: none; padding: 0.18rem 0.5rem; border-radius: 4px; background: {{ $accessSortBy === 'date' ? 'rgba(99,102,241,0.12)' : 'rgba(255,255,255,0.04)' }}; display: inline-flex; align-items: center; gap: 0.2rem; font-weight: 500;"
                                   class="sort-header" title="Sortuj po dacie ważności">
                                    <span>Data</span>
                                    <span style="opacity: {{ $accessSortBy === 'date' ? '1' : '0.3' }}; font-size: 0.65rem;">{{ $accessSortBy === 'date' ? ($accessSortDir === 'asc' ? '▲' : '▼') : '▲' }}</span>
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Drzewko uprawnień --}}
                    @if($selectedAccesses && $selectedAccesses->count() > 0)
                        @php
                            // Buduj drzewko: root modules → children → operations
                            $tree = [];
                            foreach ($selectedAccesses as $access) {
                                $modul = $access->modul;
                                if (!$modul) continue;
                                // Znajdź root (najwyższy rodzic)
                                $rootId   = $modul->parent_id ? ($modul->parent->parent_id ? null : $modul->parent_id) : $modul->id;
                                $rootName = $modul->parent_id ? ($modul->parent->nazwa ?? $modul->parent_id) : $modul->nazwa;
                                $leafId   = $modul->id;
                                $leafName = $modul->nazwa;
                                $isChild  = (bool) $modul->parent_id;

                                if (!isset($tree[$rootName])) {
                                    $tree[$rootName] = [];
                                }
                                $key = $isChild ? $leafName : '__root__';
                                if (!isset($tree[$rootName][$key])) {
                                    $tree[$rootName][$key] = [];
                                }
                                $tree[$rootName][$key][] = $access;
                            }

                            if ($accessSortBy === 'module') {
                                uksort($tree, fn($a, $b) => ($accessSortDir === 'desc' ? -1 : 1) * strnatcasecmp($a, $b));
                                foreach ($tree as $rName => &$submods) {
                                    uksort($submods, fn($a, $b) => ($accessSortDir === 'desc' ? -1 : 1) * strnatcasecmp($a, $b));
                                    foreach ($submods as $sName => &$accList) {
                                        usort($accList, function($a, $b) {
                                            $fromA = $a->valid_from ? $a->valid_from->format('Y-m-d') : '0000-00-00';
                                            $fromB = $b->valid_from ? $b->valid_from->format('Y-m-d') : '0000-00-00';
                                            if ($fromA !== $fromB) {
                                                return strcmp($fromA, $fromB);
                                            }
                                            $toA = $a->valid_to ? $a->valid_to->format('Y-m-d') : '9999-99-99';
                                            $toB = $b->valid_to ? $b->valid_to->format('Y-m-d') : '9999-99-99';
                                            return strcmp($toA, $toB);
                                        });
                                    }
                                }
                                unset($submods, $accList);
                            } elseif ($accessSortBy === 'date') {
                                uksort($tree, function($a, $b) use ($tree, $accessSortDir) {
                                    $getDate = function($branch) use ($accessSortDir) {
                                        $dates = [];
                                        foreach ($branch as $accs) {
                                            foreach ($accs as $acc) {
                                                $dates[] = $acc->valid_from ? $acc->valid_from->format('Y-m-d') : ($accessSortDir === 'desc' ? '0000-00-00' : '9999-99-99');
                                            }
                                        }
                                        sort($dates);
                                        return $accessSortDir === 'desc' ? end($dates) : ($dates[0] ?? '');
                                    };
                                    $dateA = $getDate($tree[$a]);
                                    $dateB = $getDate($tree[$b]);
                                    if ($dateA !== $dateB) {
                                        return ($accessSortDir === 'desc' ? -1 : 1) * strcmp($dateA, $dateB);
                                    }
                                    return strnatcasecmp($a, $b);
                                });

                                foreach ($tree as $rName => &$submods) {
                                    foreach ($submods as $sName => &$accList) {
                                        usort($accList, function($a, $b) use ($accessSortDir) {
                                            $fromA = $a->valid_from ? $a->valid_from->format('Y-m-d') : ($accessSortDir === 'desc' ? '0000-00-00' : '9999-99-99');
                                            $fromB = $b->valid_from ? $b->valid_from->format('Y-m-d') : ($accessSortDir === 'desc' ? '0000-00-00' : '9999-99-99');
                                            if ($fromA !== $fromB) {
                                                return ($accessSortDir === 'desc' ? -1 : 1) * strcmp($fromA, $fromB);
                                            }
                                            $toA = $a->valid_to ? $a->valid_to->format('Y-m-d') : ($accessSortDir === 'desc' ? '0000-00-00' : '9999-99-99');
                                            $toB = $b->valid_to ? $b->valid_to->format('Y-m-d') : ($accessSortDir === 'desc' ? '0000-00-00' : '9999-99-99');
                                            return ($accessSortDir === 'desc' ? -1 : 1) * strcmp($toA, $toB);
                                        });
                                    }
                                }
                                unset($submods, $accList);
                            }
                        @endphp

                        <div style="display: grid; gap: 0.75rem;">
                            @foreach($tree as $rootName => $children)
                                <div class="module-wrapper">
                                    <!-- Sekcja Głównego Modułu -->
                                    <div class="module-header" style="cursor: pointer;" onclick="toggleModuleContent(this)">
                                        <div class="module-controls">
                                            <span class="arrow-icon">▼</span>
                                        </div>
                                        <div class="module-title">{{ $rootName }}</div>
                                    </div>

                                    <!-- Sekcja Podmodułów (Kontener rozwijany) -->
                                    <div class="module-content">
                                        @foreach($children as $childName => $accesses)
                                            @foreach($accesses as $access)
                                                @php $isValid = $access->isValid(); @endphp
                                                <!-- Pojedynczy podmoduł (wiersz) -->
                                                <div class="submodule-item">
                                                    <div class="submodule-icon-area">
                                                        🔑
                                                    </div>
                                                    <div class="submodule-label-area">
                                                        <div class="submodule-label" title="{{ $childName === '__root__' ? $rootName : $childName }} — {{ $access->operacja->nazwa ?? '—' }}">
                                                            {{ $childName === '__root__' ? $rootName : $childName }}
                                                        </div>
                                                    </div>
                                                    <div class="submodule-status-area">
                                                        <div>Status:</div>
                                                        <div class="{{ $isValid ? 'submodule-status-active' : 'submodule-status-inactive' }}">
                                                            {{ $isValid ? 'Aktywny' : 'Nieaktywny' }}
                                                        </div>
                                                    </div>
                                                    <div class="submodule-date-area">
                                                        <div>od: {{ $access->valid_from ? $access->valid_from->format('Y-m-d') : '∞' }}</div>
                                                        <div>do: {{ $access->valid_to ? $access->valid_to->format('Y-m-d') : '∞' }}</div>
                                                    </div>
                                                    <div class="submodule-action-area">
                                                        <a href="#" class="action-link" onclick="openMoreInfo({{ $access->user_id }}, {{ $access->p_modul_id }}, {{ $access->p_operacje_id }}, '{{ ($childName === '__root__' ? $rootName : $childName) . ' — ' . ($access->operacja->nazwa ?? '') }}', {{ $access->id }}); return false;">więcej info</a>
                                                        @if($role === 'admin')
                                                            <a href="{{ route('access.edit', $access->id) }}" title="Edytuj uprawnienie" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.15rem; transition: color 0.12s; display: inline-flex;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">✏️</a>
                                                            
                                                            <form action="{{ route('access.destroy', $access->id) }}" method="POST" style="margin: 0; display: inline-flex; align-items: center;" onsubmit="return confirm('Usunąć to uprawnienie?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" title="Usuń" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.15rem; font-size: 0.85rem; line-height: 1; transition: color 0.12s;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">✕</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div style="margin-top: 0.6rem; color: var(--text-muted); font-size: 0.75rem; text-align: right;">
                            {{ $selectedAccesses->count() }} uprawnień w {{ count($tree) }} {{ count($tree) === 1 ? 'module' : 'modułach' }}
                        </div>
                    @else
                        <div class="card" style="padding: 2.25rem; text-align: center;">
                            <div style="font-size: 1.8rem; margin-bottom: 0.5rem;">🔒</div>
                            <div style="color: var(--text-muted); font-size: 0.88rem;">Brak przypisanych uprawnień</div>
                            @if($role === 'admin')
                                <a href="{{ route('access.create', ['user_id' => $selectedUser->id]) }}"
                                   style="display: inline-block; margin-top: 0.75rem; color: var(--primary); font-size: 0.82rem; text-decoration: none; background: rgba(99,102,241,0.1); padding: 0.35rem 0.8rem; border-radius: 6px;">
                                    + Dodaj pierwsze uprawnienie
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Dialog modal "Więcej info" --}}
        <dialog id="history-dialog" style="border: none; border-radius: 12px; background: var(--surface); color: var(--text-main); padding: 1.75rem 2rem; max-width: 600px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); outline: none; border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                <h2 id="dialog-title" style="font-size: 1.1rem; font-weight: 600; color: var(--text-main); margin: 0;">Więcej informacji o uprawnieniu</h2>
                <button onclick="document.getElementById('history-dialog').close()" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem; line-height: 1; padding: 0; display: inline-flex;">✕</button>
            </div>

            <div style="display: grid; gap: 1.25rem;">
                <!-- Aktywne dane -->
                <div id="dialog-active-info" style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 8px; padding: 0.85rem 1.1rem; font-size: 0.82rem;">
                    <h3 style="font-size: 0.85rem; margin-top: 0; margin-bottom: 0.5rem; color: var(--primary); font-weight: 600;">Aktualny stan dostępu</h3>
                    <div style="display: grid; grid-template-columns: 100px 1fr; gap: 0.4rem; line-height: 1.4;">
                        <div style="color: var(--text-muted); font-weight: 500;">Status:</div>
                        <div id="active-status" style="font-weight: 600;">—</div>
                        
                        <div style="color: var(--text-muted); font-weight: 500;">Login:</div>
                        <div id="active-login">—</div>

                        <div style="color: var(--text-muted); font-weight: 500;">Ważność:</div>
                        <div id="active-validity">—</div>

                        <div style="color: var(--text-muted); font-weight: 500;">Uwagi:</div>
                        <div id="active-uwagi" style="white-space: pre-wrap;">—</div>
                    </div>
                </div>

                <!-- Tabela historyczna -->
                <div>
                    <h3 style="font-size: 0.85rem; margin-bottom: 0.5rem; color: var(--text-main); font-weight: 600;">Archiwum i historia operacji</h3>
                    <div style="max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; background: rgba(0,0,0,0.15);">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); background: rgba(255,255,255,0.02); color: var(--text-muted);">
                                    <th style="padding: 0.5rem; font-weight: 500;">Data</th>
                                    <th style="padding: 0.5rem; font-weight: 500;">Akcja</th>
                                    <th style="padding: 0.5rem; font-weight: 500;">Login</th>
                                    <th style="padding: 0.5rem; font-weight: 500;">Okres</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body">
                                <!-- Wiersze wstawiane przez JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </dialog>
    </main>
@endsection

@push('scripts')
<style>
    dialog::backdrop {
        background-color: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
    }
    .sort-header:hover {
        color: var(--primary) !important;
    }
</style>
<script>
    const searchInput = document.getElementById('search-input');
    let debounceTimer;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                document.getElementById('search-form').submit();
            }, 400);
        });
    }
    // Hover effect
    document.querySelectorAll('.user-row:not(.selected)').forEach(el => {
        el.addEventListener('mouseover', () => el.style.background = 'rgba(255,255,255,0.03)');
        el.addEventListener('mouseout',  () => el.style.background = 'transparent');
    });

    // Toggle module-content
    function toggleModuleContent(header) {
        const content = header.nextElementSibling;
        const arrow = header.querySelector('.arrow-icon');
        if (content.style.display === 'none') {
            content.style.display = 'block';
            arrow.textContent = '▼';
        } else {
            content.style.display = 'none';
            arrow.textContent = '▶';
        }
    }

    // Więcej info modal handler
    function openMoreInfo(userId, modulId, operacjeId, title, accessId = null) {
        document.getElementById('dialog-title').textContent = title;
        
        const dialog = document.getElementById('history-dialog');
        const activeInfo = document.getElementById('dialog-active-info');
        const historyBody = document.getElementById('history-table-body');
        
        // Reset content
        document.getElementById('active-status').textContent = 'Ładowanie...';
        document.getElementById('active-login').textContent = '—';
        document.getElementById('active-validity').textContent = '—';
        document.getElementById('active-uwagi').textContent = '—';
        historyBody.innerHTML = '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: var(--text-muted);">Ładowanie historii...</td></tr>';
        
        dialog.showModal();

        let url = `/permissions/access/history?user_id=${userId}&p_modul_id=${modulId}&p_operacje_id=${operacjeId}`;
        if (accessId) {
            url += `&access_id=${accessId}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update active state
                    if (data.active) {
                        activeInfo.style.display = 'block';
                        const statusEl = document.getElementById('active-status');
                        statusEl.textContent = data.active.status;
                        statusEl.className = data.active.status === 'Aktywny' ? 'submodule-status-active' : 'submodule-status-inactive';
                        document.getElementById('active-login').textContent = data.active.login || 'Brak';
                        document.getElementById('active-validity').textContent = `od ${data.active.valid_from} do ${data.active.valid_to}`;
                        document.getElementById('active-uwagi').textContent = data.active.uwagi || 'Brak uwag';
                    } else {
                        activeInfo.style.display = 'none';
                    }

                    // Update history table
                    historyBody.innerHTML = '';
                    if (data.history.length === 0) {
                        historyBody.innerHTML = '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: var(--text-muted);">Brak wpisów w historii.</td></tr>';
                    } else {
                        data.history.forEach(item => {
                            const tr = document.createElement('tr');
                            tr.style.borderBottom = '1px solid rgba(255,255,255,0.03)';
                            
                            let actionColor = 'var(--text-main)';
                            if (item.action.toLowerCase() === 'nadano') actionColor = 'var(--success)';
                            if (item.action.toLowerCase() === 'odebrano') actionColor = 'var(--danger)';
                            if (item.action.toLowerCase() === 'wygasło') actionColor = '#94a3b8';
                            if (item.action.toLowerCase() === 'zaktualizowano') actionColor = 'var(--primary)';
                            
                            tr.innerHTML = `
                                <td style="padding: 0.5rem; color: var(--text-muted);" title="Uwagi: ${item.uwagi}">${item.date}</td>
                                <td style="padding: 0.5rem; font-weight: 600; color: ${actionColor};" title="Uwagi: ${item.uwagi}">${item.action}</td>
                                <td style="padding: 0.5rem;" title="Uwagi: ${item.uwagi}">${item.login}</td>
                                <td style="padding: 0.5rem;" title="Uwagi: ${item.uwagi}">od ${item.valid_from} do ${item.valid_to}</td>
                            `;
                            historyBody.appendChild(tr);
                        });
                    }
                }
            })
            .catch(err => {
                console.error(err);
                historyBody.innerHTML = '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: var(--danger);">Błąd podczas ładowania danych.</td></tr>';
            });
    }
</script>
@endpush
