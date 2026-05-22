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

                {{-- Wyczyść --}}
                @if($search || $deptId)
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
                    <div style="padding: 0.75rem 1.1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase;">Użytkownicy</span>
                        @if($deptId)
                            @php $deptName = $departments->firstWhere('ID_Departament', $deptId)?->Nazwa; @endphp
                            @if($deptName)
                                <span style="font-size: 0.73rem; background: rgba(99,102,241,0.12); color: var(--primary); padding: 0.15rem 0.55rem; border-radius: 999px;">{{ $deptName }}</span>
                            @endif
                        @endif
                    </div>

                    @forelse($users as $user)
                        @php $isSelected = $selectedUser && $selectedUser->id === $user->id; @endphp
                        <a href="{{ route('access.index', array_filter(['user_id' => $user->id, 'search' => $search, 'dept_id' => $deptId])) }}"
                           style="display: flex; align-items: center; gap: 0.8rem; padding: 0.75rem 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.04); text-decoration: none; background: {{ $isSelected ? 'rgba(99,102,241,0.1)' : 'transparent' }}; transition: background 0.12s;"
                           class="user-row {{ $isSelected ? 'selected' : '' }}">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: {{ $isSelected ? 'var(--primary)' : 'rgba(99,102,241,0.18)' }}; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: {{ $isSelected ? 'white' : 'var(--primary)' }}; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-weight: 500; font-size: 0.875rem; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->name }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $user->email }}</div>
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
                                        @if($selectedUser->departments->isNotEmpty())
                                            &bull; {{ $selectedUser->departments->pluck('Nazwa')->join(', ') }}
                                        @endif
                                    </div>
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
                        @endphp

                        <div style="display: grid; gap: 0.75rem;">
                            @foreach($tree as $rootName => $children)
                                <div class="card" style="padding: 0; overflow: hidden;">
                                    {{-- Nagłówek root --}}
                                    <div style="padding: 0.8rem 1.1rem; background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.6rem;">
                                        <span style="font-size: 0.85rem;">📦</span>
                                        <span style="font-weight: 600; color: var(--text-color); font-size: 0.88rem;">{{ $rootName }}</span>
                                    </div>

                                    <div style="padding: 0.65rem 1.1rem; display: grid; gap: 0.5rem;">
                                        @foreach($children as $childName => $accesses)
                                            <div>
                                                @if($childName !== '__root__')
                                                    {{-- Podmoduł --}}
                                                    <div style="display: flex; align-items: center; gap: 0.45rem; margin-bottom: 0.35rem;">
                                                        <span style="color: var(--text-muted); font-size: 0.75rem; padding-left: 0.5rem;">↳</span>
                                                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">{{ $childName }}</span>
                                                    </div>
                                                @endif
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; {{ $childName !== '__root__' ? 'padding-left: 1.3rem;' : '' }}">
                                                    @foreach($accesses as $access)
                                                        @php $isValid = $access->isValid(); @endphp
                                                        <div class="perm-capsule" style="display: inline-flex; align-items: center; background: {{ $isValid ? 'rgba(99, 102, 241, 0.05)' : 'rgba(239, 68, 68, 0.05)' }}; border: 1px solid {{ $isValid ? 'rgba(99, 102, 241, 0.18)' : 'rgba(239, 68, 68, 0.2)' }}; border-radius: 999px; padding: 0.22rem 0.45rem 0.22rem 0.75rem; gap: 0.4rem; transition: all 0.15s; margin-bottom: 0.2rem;" onmouseover="this.style.borderColor='{{ $isValid ? 'rgba(99, 102, 241, 0.4)' : 'rgba(239, 68, 68, 0.4)' }}'; this.style.background='{{ $isValid ? 'rgba(99, 102, 241, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}'" onmouseout="this.style.borderColor='{{ $isValid ? 'rgba(99, 102, 241, 0.18)' : 'rgba(239, 68, 68, 0.2)' }}'; this.style.background='{{ $isValid ? 'rgba(99, 102, 241, 0.05)' : 'rgba(239, 68, 68, 0.05)' }}'">
                                                            {{-- Operation Name and Dates --}}
                                                            <span style="color: {{ $isValid ? 'var(--text-color)' : 'var(--danger)' }}; font-size: 0.8rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.35rem;">
                                                                {{ $access->operacja->nazwa ?? '—' }}
                                                                @if($access->valid_from || $access->valid_to)
                                                                    <span style="font-size: 0.72rem; opacity: 0.75; font-weight: 400; color: var(--text-muted);">
                                                                        ({{ $access->valid_from ? $access->valid_from->format('Y-m-d') : '∞' }} do {{ $access->valid_to ? $access->valid_to->format('Y-m-d') : '∞' }})
                                                                    </span>
                                                                @endif
                                                                @if(!$isValid)
                                                                    <span style="font-size: 0.6rem; background: rgba(239,68,68,0.18); color: var(--danger); padding: 0.05rem 0.3rem; border-radius: 3px; font-weight: 700; text-transform: uppercase; margin-left: 0.15rem;">Wygasło</span>
                                                                @endif
                                                            </span>

                                                            {{-- Admin Actions Divider and Buttons inside the capsule --}}
                                                            @if($role === 'admin')
                                                                <span style="width: 1px; height: 12px; background: {{ $isValid ? 'rgba(99, 102, 241, 0.25)' : 'rgba(239, 68, 68, 0.25)' }}; margin: 0 0.1rem;"></span>
                                                                <div style="display: inline-flex; align-items: center; gap: 0.35rem; padding-right: 0.2rem;">
                                                                    <a href="{{ route('access.edit', $access->id) }}" title="Edytuj uprawnienie" style="color: var(--text-muted); text-decoration: none; font-size: 0.75rem; display: inline-flex; align-items: center; transition: color 0.12s; padding: 0.15rem;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">✏️</a>
                                                                    
                                                                    <form action="{{ route('access.destroy', $access->id) }}" method="POST" style="margin: 0; display: inline-flex; align-items: center;" onsubmit="return confirm('Usunąć to uprawnienie?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" title="Usuń" style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0.15rem; font-size: 0.75rem; line-height: 1; display: inline-flex; align-items: center; transition: color 0.12s;" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">✕</button>
                                                                    </form>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
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
    </main>
@endsection

@push('scripts')
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
</script>
@endpush
