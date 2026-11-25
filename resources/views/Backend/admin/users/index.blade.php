@extends('Backend.layouts.app')
@section('title', 'Użytkownicy - Admin')

@section('content')
    @include('Backend.admin._sidebar')


    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Użytkownicy</h1>
                <p>Zarządzaj użytkownikami w systemie.</p>
            </div>
            
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako {{ auth()->user()->role === 'admin' ? 'Administrator' : (auth()->user()->role === 'view' ? 'Podgląd (View)' : (auth()->user()->role === 'support' ? 'Wsparcie (Support)' : ucfirst(auth()->user()->role))) }}
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                ✓ {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: var(--danger); padding: 0.85rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                ⚠ {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Lista użytkowników ({{ $users->total() }})</span>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('users.create') }}" class="btn-add-user">
                        <span style="font-size: 1.1rem; line-height: 1;">＋</span> Dodaj użytkownika
                    </a>
                @endif
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <!-- Rozbudowana wyszukiwarka i filtry -->
                <form method="GET" action="{{ route('users.index') }}" id="search-form" style="margin-bottom: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.15rem;">
                    @if($sortBy !== 'name')
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    @endif
                    @if($sortDir !== 'asc')
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    @endif

                    {{-- Duży pasek wyszukiwania --}}
                    <div style="position: relative; margin-bottom: 0.9rem;">
                        <span style="position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 1rem;">🔍</span>
                        <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}"
                               placeholder="Szukaj użytkownika po imieniu, nazwisku, adresie e-mail lub historii zmian..."
                               class="form-control" style="width: 100%; padding: 0 2.5rem 0 2.5rem; height: 49px; font-size: 0.95rem;">
                        @if(!empty($search))
                            <a href="{{ route('users.index', array_filter(['status' => $status ?? '', 'role' => $roleFilter ?? '', 'dept_id' => $deptId ?? '', 'dept_tag_id' => $deptTagId ?? '', 'dept_status' => $deptStatus ?? '', 'dept_from' => $deptFrom ?? '', 'dept_to' => $deptTo ?? '', 'per_page' => ($perPage ?? 20) != 20 ? $perPage : null])) }}"
                               style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 1.1rem;" title="Wyczyść wyszukiwanie">✕</a>
                        @endif
                    </div>

                    {{-- Siatka filtrów: Konto + Wydziały i tagi --}}
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.9rem; align-items: start;">
                        {{-- Grupa 1: Konto i rola --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem;">
                            <select name="status" id="status-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                <option value="">Wszystkie statusy konta</option>
                                <option value="active" {{ ($status ?? '') === 'active' || ($status ?? '') === '1' ? 'selected' : '' }}>Tylko aktywni</option>
                                <option value="inactive" {{ ($status ?? '') === 'inactive' || ($status ?? '') === '0' ? 'selected' : '' }}>Tylko nieaktywni</option>
                            </select>

                            <select name="role" id="role-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                <option value="">Wszystkie role</option>
                                <option value="admin" {{ ($roleFilter ?? '') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                <option value="mod" {{ ($roleFilter ?? '') === 'mod' ? 'selected' : '' }}>Moderator</option>
                                <option value="support" {{ ($roleFilter ?? '') === 'support' ? 'selected' : '' }}>Wsparcie (Support)</option>
                                <option value="view" {{ ($roleFilter ?? '') === 'view' ? 'selected' : '' }}>Podgląd (View)</option>
                                <option value="user" {{ ($roleFilter ?? '') === 'user' ? 'selected' : '' }}>Użytkownik</option>
                                <option value="none" {{ ($roleFilter ?? '') === 'none' ? 'selected' : '' }}>Brak roli</option>
                            </select>

                            <select name="per_page" id="per-page-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                @foreach([10, 20, 50, 100] as $limit)
                                    <option value="{{ $limit }}" {{ ($perPage ?? 20) == $limit ? 'selected' : '' }}>{{ $limit }} / strona</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Grupa 2: Wydział, tagi i okresy --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.6rem;">
                            <select name="dept_id" id="dept-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                <option value="">Wszystkie wydziały</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept->ID_Departament }}" {{ ($deptId ?? '') == $dept->ID_Departament ? 'selected' : '' }}>
                                        {{ $dept->Nazwa }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="dept_tag_id" id="dept-tag-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                <option value="">Wszystkie tagi wydziałów</option>
                                @foreach($tags ?? [] as $tag)
                                    <option value="{{ $tag->id }}" {{ ($deptTagId ?? '') == $tag->id ? 'selected' : '' }}>
                                        #{{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="dept_status" id="dept-status-filter" class="form-control" style="font-size: 0.84rem; height: 43px; padding: 0 0.75rem;" onchange="document.getElementById('search-form').submit()">
                                <option value="">Wszystkie okresy wydziału</option>
                                <option value="active" {{ ($deptStatus ?? '') === 'active' ? 'selected' : '' }}>Tylko aktywny wydział</option>
                                <option value="expired" {{ ($deptStatus ?? '') === 'expired' ? 'selected' : '' }}>Z wygasłym wydziałem</option>
                                <option value="none" {{ ($deptStatus ?? '') === 'none' ? 'selected' : '' }}>Bez wydziału</option>
                            </select>
                        </div>
                    </div>

                    {{-- Dolny wiersz: Daty wydziału + przyciski akcji --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-top: 0.8rem; padding-top: 0.8rem; border-top: 1px solid rgba(255,255,255,0.05); flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.45rem; background: rgba(0,0,0,0.15); border: 1px solid var(--border); border-radius: 6px; padding: 0.25rem 0.65rem;">
                            <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; white-space: nowrap;">Okres wydziału:</span>
                            <input type="date" name="dept_from" id="dept_from" value="{{ $deptFrom ?? '' }}" class="form-control" style="width: auto; padding: 0.25rem 0.45rem; font-size: 0.8rem; height: 37px;" title="Przynależność do wydziału od" placeholder="Od">
                            <span style="font-size: 0.78rem; color: var(--text-muted);">-</span>
                            <input type="date" name="dept_to" id="dept_to" value="{{ $deptTo ?? '' }}" class="form-control" style="width: auto; padding: 0.25rem 0.45rem; font-size: 0.8rem; height: 37px;" title="Przynależność do wydziału do" placeholder="Do">
                        </div>

                        <div style="display: flex; gap: 0.6rem; align-items: center; margin-left: auto;">
                            @if(!empty($search) || !empty($status) || !empty($roleFilter) || !empty($deptId) || !empty($deptTagId) || !empty($deptStatus) || !empty($deptFrom) || !empty($deptTo))
                                <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.5rem 0.9rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                                    ✕ Wyczyść filtry
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.35rem; font-size: 0.85rem; font-weight: 600;">🔍 Filtruj</button>
                        </div>
                    </div>
                </form>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'id', 'sort_dir' => ($sortBy === 'id' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        ID
                                        @if($sortBy === 'id')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        Nazwa
                                        @if($sortBy === 'name')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_dir' => ($sortBy === 'email' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" class="sort-header">
                                        Email
                                        @if($sortBy === 'email')
                                            <span style="font-size: 0.75rem;">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>
                                        @else
                                            <span style="font-size: 0.75rem; opacity: 0.3;">⇅</span>
                                        @endif
                                    </a>
                                </th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Wydział</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Rola</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted);">Status</th>
                                <th style="padding: 1rem; font-weight: 600; color: var(--text-muted); text-align: right;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;"
                                    onmouseover="this.style.background='rgba(99,102,241,0.04)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem; color: var(--text-muted); font-size: 0.875rem;">{{ $user->id }}</td>
                                    <td style="padding: 1rem; color: var(--text-main); font-weight: 500;">{{ $user->name }}</td>
                                    <td style="padding: 1rem; color: var(--text-muted);">{{ $user->email }}</td>
                                    <td style="padding: 1rem;">
                                        @if($user->departments->count() > 0)
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                                @foreach($user->sortedDepartments as $dept)
                                                    @php
                                                        $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                                    @endphp
                                                    <span title="{{ $dept->Nazwa }}@if($dept->pivot->od) (od: {{ $dept->pivot->od }})@endif @if($dept->pivot->do) (do: {{ $dept->pivot->do }})@endif @if($isExpired) - NIEAKTYWNY @endif"
                                                          style="background: {{ $isExpired ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.1)' }}; color: {{ $isExpired ? '#ef4444' : 'var(--success)' }}; border: 1px solid {{ $isExpired ? 'rgba(239, 68, 68, 0.3)' : 'transparent' }}; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.7rem; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                        <span>{{ $dept->Nazwa }}</span>
                                                        @if($dept->pivot->do)
                                                            <span style="color: {{ $isExpired ? '#ef4444' : 'inherit' }}; font-weight: {{ $isExpired ? '700' : '500' }}; font-size: 0.65rem;">
                                                                (do: {{ $dept->pivot->do }})
                                                            </span>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.875rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                            {{ ucfirst($user->role ?? 'Brak') }}
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;">
                                        @if($user->is_active)
                                            <span style="color: var(--success); font-size: 0.875rem; font-weight: 500;">● Aktywny</span>
                                        @else
                                            <span style="color: var(--danger); font-size: 0.875rem; font-weight: 500;">● Nieaktywny</span>
                                        @endif
                                    </td>
                                    <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                                        @if(auth()->user()->role === 'admin')
                                            <a href="{{ route('users.edit', $user->id) }}" class="btn-table-action">
                                                ✎ Edytuj
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline-block; margin-left: 0.5rem;" onsubmit="return confirm('Czy na pewno usunąć użytkownika {{ addslashes($user->name) }}? Operacja jest nieodwracalna.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="background: none; border: 1px solid var(--danger); color: var(--danger); cursor: pointer; font-size: 0.78rem; padding: 0.3rem 0.65rem; border-radius: 5px; transition: background 0.15s;" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='none'">
                                                        🗑 Usuń
                                                    </button>
                                                </form>
                                            @endif
                                        @elseif(auth()->user()->role === 'support')
                                            <a href="{{ route('access.index', ['user_id' => $user->id]) }}" class="btn-table-action" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                                🔑 Zarządzaj uprawnieniami
                                            </a>
                                        @else
                                            <a href="{{ route('users.show', $user->id) }}" class="btn-table-action" style="background: rgba(99,102,241,0.1); color: var(--primary);">
                                                👁 Podgląd danych
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-muted);">Brak użytkowników do wyświetlenia.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginacja --}}
                @if($users->total() > 0)
                    <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <span style="font-size: 0.82rem; color: var(--text-muted);">
                            Wyświetlono {{ $users->firstItem() }}–{{ $users->lastItem() }} z {{ $users->total() }} użytkowników
                        </span>

                        @if($users->hasPages())
                            <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                                @if($users->onFirstPage())
                                    <span style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); opacity: 0.45; border: 1px solid var(--border);">← Poprzednia</span>
                                @else
                                    <a href="{{ $users->previousPageUrl() }}" style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25);">← Poprzednia</a>
                                @endif

                                @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                                    @if($page == $users->currentPage())
                                        <span style="padding: 0.35rem 0.7rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; color: white; background: var(--primary);">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" style="padding: 0.35rem 0.7rem; border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); text-decoration: none; border: 1px solid var(--border);">{{ $page }}</a>
                                    @endif
                                @endforeach

                                @if($users->hasMorePages())
                                    <a href="{{ $users->nextPageUrl() }}" style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: var(--primary); text-decoration: none; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25);">Następna →</a>
                                @else
                                    <span style="padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; color: var(--text-muted); opacity: 0.45; border: 1px solid var(--border);">Następna →</span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection

@push('styles')
<style>
    .sort-header:hover {
        color: var(--primary) !important;
    }
</style>
@endpush

@push('scripts')
<script>
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                document.getElementById('search-form').submit();
            }, 500);
        });

        // Set focus to the end of input text
        const len = searchInput.value.length;
        if (len > 0) {
            searchInput.focus();
            searchInput.setSelectionRange(len, len);
        }
    }
</script>
@endpush
