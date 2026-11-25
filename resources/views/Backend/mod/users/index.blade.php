@extends('Backend.layouts.app')
@section('title', 'Użytkownicy - Moderator')

@section('content')
    @include('Backend.mod._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Użytkownicy</h1>
                <p>Przeglądaj listę użytkowników w twoim wydziale.</p>
            </div>
            <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; font-weight: 600;">
                Zalogowano jako Moderator
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Lista użytkowników ({{ $users->total() }})
            </div>
            <div style="padding: 1rem 1.25rem 0.75rem 1.25rem;">
                <!-- Rozbudowana wyszukiwarka i filtry -->
                <form method="GET" action="{{ route('users.index') }}" id="search-form" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 0.9rem 1rem;">
                    @if($sortBy !== 'name')
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                    @endif
                    @if($sortDir !== 'asc')
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    @endif

                    <div style="position: relative; margin-bottom: 0.75rem;">
                        <span style="position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 1rem;">🔍</span>
                        <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}"
                               placeholder="Szukaj użytkownika po nazwie, emailu lub historii..."
                               class="form-control" style="width: 100%; padding-left: 2.5rem; padding-right: 2.5rem; height: 42px; font-size: 0.92rem;">
                        @if(!empty($search))
                            <a href="{{ route('users.index', array_filter(['status' => $status ?? '', 'role' => $roleFilter ?? '', 'dept_id' => $deptId ?? '', 'dept_tag_id' => $deptTagId ?? '', 'dept_status' => $deptStatus ?? '', 'dept_from' => $deptFrom ?? '', 'dept_to' => $deptTo ?? '', 'per_page' => ($perPage ?? 20) != 20 ? $perPage : null])) }}"
                               style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 1.1rem;" title="Wyczyść wyszukiwanie">✕</a>
                        @endif
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.55rem;">
                        <select name="status" id="status-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie statusy konta</option>
                            <option value="active" {{ ($status ?? '') === 'active' || ($status ?? '') === '1' ? 'selected' : '' }}>Tylko aktywni</option>
                            <option value="inactive" {{ ($status ?? '') === 'inactive' || ($status ?? '') === '0' ? 'selected' : '' }}>Tylko nieaktywni</option>
                        </select>

                        <select name="role" id="role-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie role</option>
                            <option value="admin" {{ ($roleFilter ?? '') === 'admin' ? 'selected' : '' }}>Administrator</option>
                            <option value="mod" {{ ($roleFilter ?? '') === 'mod' ? 'selected' : '' }}>Moderator</option>
                            <option value="support" {{ ($roleFilter ?? '') === 'support' ? 'selected' : '' }}>Wsparcie (Support)</option>
                            <option value="view" {{ ($roleFilter ?? '') === 'view' ? 'selected' : '' }}>Podgląd (View)</option>
                            <option value="user" {{ ($roleFilter ?? '') === 'user' ? 'selected' : '' }}>Użytkownik</option>
                            <option value="none" {{ ($roleFilter ?? '') === 'none' ? 'selected' : '' }}>Brak roli</option>
                        </select>

                        <select name="dept_id" id="dept-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie wydziały</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->ID_Departament }}" {{ ($deptId ?? '') == $dept->ID_Departament ? 'selected' : '' }}>
                                    {{ $dept->Nazwa }}
                                </option>
                            @endforeach
                        </select>

                        <select name="dept_tag_id" id="dept-tag-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie tagi wydziałów</option>
                            @foreach($tags ?? [] as $tag)
                                <option value="{{ $tag->id }}" {{ ($deptTagId ?? '') == $tag->id ? 'selected' : '' }}>
                                    #{{ $tag->name }}
                                </option>
                            @endforeach
                        </select>

                        <select name="dept_status" id="dept-status-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie okresy wydziału</option>
                            <option value="active" {{ ($deptStatus ?? '') === 'active' ? 'selected' : '' }}>Tylko aktywny wydział</option>
                            <option value="expired" {{ ($deptStatus ?? '') === 'expired' ? 'selected' : '' }}>Z wygasłym wydziałem</option>
                            <option value="none" {{ ($deptStatus ?? '') === 'none' ? 'selected' : '' }}>Bez wydziału</option>
                        </select>

                        <select name="per_page" id="per-page-filter" class="form-control" style="font-size: 0.83rem; height: 38px;" onchange="document.getElementById('search-form').submit()">
                            @foreach([10, 20, 50, 100] as $limit)
                                <option value="{{ $limit }}" {{ ($perPage ?? 20) == $limit ? 'selected' : '' }}>{{ $limit }} / strona</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.65rem; margin-top: 0.7rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.35rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 6px; padding: 0.2rem 0.6rem;">
                            <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 500; white-space: nowrap;">Wydział:</span>
                            <input type="date" name="dept_from" id="dept_from" value="{{ $deptFrom ?? '' }}" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8rem;" title="Przynależność do wydziału od" placeholder="Od">
                            <span style="font-size: 0.78rem; color: var(--text-muted);">-</span>
                            <input type="date" name="dept_to" id="dept_to" value="{{ $deptTo ?? '' }}" class="form-control" style="width: auto; padding: 0.35rem 0.5rem; font-size: 0.8rem;" title="Przynależność do wydziału do" placeholder="Do">
                        </div>

                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-left: auto;">
                            @if(!empty($search) || !empty($status) || !empty($roleFilter) || !empty($deptId) || !empty($deptTagId) || !empty($deptStatus) || !empty($deptFrom) || !empty($deptTo))
                                <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                                    ✕ Wyczyść
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem;">Filtruj</button>
                        </div>
                    </div>
                </form>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.85rem;">
                            <th style="padding: 0.85rem 1rem;">
                                <span style="margin-right: 0.5rem;">Użytkownik</span>
                                <span style="font-weight: normal; font-size: 0.8rem;">
                                    (Sortuj: 
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => ($sortBy === 'name' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: {{ $sortBy === 'name' ? 'var(--primary)' : 'inherit' }}; text-decoration: none; display: inline-flex; align-items: center; gap: 0.15rem;" class="sort-header">
                                        Nazwa @if($sortBy === 'name') <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span> @endif
                                    </a>
                                    | 
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_dir' => ($sortBy === 'email' && $sortDir === 'asc') ? 'desc' : 'asc']) }}" style="color: {{ $sortBy === 'email' ? 'var(--primary)' : 'inherit' }}; text-decoration: none; display: inline-flex; align-items: center; gap: 0.15rem;" class="sort-header">
                                        Email @if($sortBy === 'email') <span>{{ $sortDir === 'asc' ? '▲' : '▼' }}</span> @endif
                                    </a>
                                    )
                                </span>
                            </th>
                            <th style="padding: 0.85rem 1rem;">Rola</th>
                            <th style="padding: 0.85rem 1rem;">Status</th>
                            <th style="padding: 0.85rem 1rem;">Zarejestrowano</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 0.85rem 1rem;">
                                    <div style="font-weight: 500; color: var(--text-color);">{{ $user->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $user->email }}</div>
                                    @if($user->departments && $user->departments->isNotEmpty())
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.35rem;">
                                            @foreach($user->sortedDepartments as $dept)
                                                @php
                                                    $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                                                @endphp
                                                <span title="{{ $dept->Nazwa }}@if($dept->pivot->od) (od: {{ $dept->pivot->od }})@endif @if($dept->pivot->do) (do: {{ $dept->pivot->do }})@endif @if($isExpired) - NIEAKTYWNY @endif"
                                                      style="background: {{ $isExpired ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.12)' }}; color: {{ $isExpired ? '#ef4444' : 'var(--success)' }}; border: 1px solid {{ $isExpired ? 'rgba(239, 68, 68, 0.3)' : 'transparent' }}; padding: 0.15rem 0.45rem; border-radius: 999px; font-size: 0.68rem; font-weight: 600; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.2rem;">
                                                    <span>{{ $dept->Nazwa }}</span>
                                                    @if($dept->pivot->do)
                                                        <span style="color: {{ $isExpired ? '#ef4444' : 'inherit' }}; font-weight: {{ $isExpired ? '700' : '500' }}; font-size: 0.65rem;">
                                                            (do: {{ $dept->pivot->do }})
                                                        </span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                        {{ ucfirst($user->role ?? 'Brak') }}
                                    </span>
                                </td>
                                <td style="padding: 0.85rem 1rem;">
                                    @if($user->is_active)
                                        <span style="color: var(--success); font-size: 0.8rem;">● Aktywny</span>
                                    @else
                                        <span style="color: var(--danger); font-size: 0.8rem;">● Zablokowany</span>
                                    @endif
                                </td>
                                <td style="padding: 0.85rem 1rem; color: var(--text-muted); font-size: 0.85rem;">
                                    {{ $user->created_at->format('Y-m-d') }}
                                </td>
                                <td style="padding: 0.85rem 1rem; text-align: right;">
                                    <a href="{{ route('users.permissions', $user->id) }}" style="color: var(--primary); text-decoration: none; font-size: 0.85rem; background: rgba(99,102,241,0.1); padding: 0.3rem 0.75rem; border-radius: 6px;">
                                        Uprawnienia
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">Brak użytkowników do wyświetlenia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginacja --}}
            @if($users->total() > 0)
                <div style="padding: 0.9rem 1.25rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
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
