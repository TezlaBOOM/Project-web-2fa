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
                Lista użytkowników
            </div>
            <div style="padding: 1rem 1rem 0 1rem;">
                <!-- Wyszukiwarka i filtry -->
                <form method="GET" action="{{ route('users.index') }}" id="search-form" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
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
                                            @foreach($user->departments as $dept)
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
