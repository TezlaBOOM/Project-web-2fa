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
                Zalogowano jako Administrator
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
                <span>Lista użytkowników</span>
                <a href="{{ route('users.create') }}" class="btn-add-user">
                    <span style="font-size: 1.1rem; line-height: 1;">＋</span> Dodaj użytkownika
                </a>
            </div>
            <div class="card-body" style="padding: 1.5rem;">
                <!-- Wyszukiwarka i filtry -->
                <form method="GET" action="{{ route('users.index') }}" id="search-form" style="margin-bottom: 1.25rem; display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; max-width: 400px; min-width: 200px;">
                        <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}" 
                               placeholder="Szukaj po nazwie, emailu lub historii..." 
                               class="form-control" style="width: 100%; padding-right: 2rem;">
                        @if(!empty($search))
                            <a href="{{ route('users.index', array_filter(['status' => $status ?? ''])) }}" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); text-decoration: none; font-size: 1.1rem;" title="Wyczyść wyszukiwanie">✕</a>
                        @endif
                    </div>

                    {{-- Filtr statusu --}}
                    <div style="min-width: 170px;">
                        <select name="status" id="status-filter" class="form-control" onchange="document.getElementById('search-form').submit()">
                            <option value="">Wszystkie statusy</option>
                            <option value="active" {{ ($status ?? '') === 'active' || ($status ?? '') === '1' ? 'selected' : '' }}>Tylko aktywni</option>
                            <option value="inactive" {{ ($status ?? '') === 'inactive' || ($status ?? '') === '0' ? 'selected' : '' }}>Tylko nieaktywni</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.25rem; font-size: 0.85rem;">Szukaj</button>
                    @if(!empty($search) || !empty($status))
                        <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; padding: 0.55rem 0.85rem; background: rgba(255,255,255,0.05); border-radius: 6px; white-space: nowrap;">
                            ✕ Wyczyść
                        </a>
                    @endif
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
                                                @foreach($user->departments as $dept)
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
