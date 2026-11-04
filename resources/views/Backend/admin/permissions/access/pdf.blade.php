<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Raport Dostępu i Uprawnień Użytkowników</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .report-header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e1b4b;
            margin: 0 0 4px 0;
        }
        .report-meta {
            font-size: 9px;
            color: #64748b;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #312e81;
            margin: 0 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #cbd5e1;
        }
        .filters-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 16px;
        }
        .filters-table {
            width: 100%;
            border-collapse: collapse;
        }
        .filters-table td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 9.5px;
        }
        .filter-label {
            font-weight: bold;
            color: #475569;
            width: 22%;
        }
        .filter-value {
            color: #0f172a;
            width: 28%;
        }
        .filter-active {
            color: #4338ca;
            font-weight: bold;
        }
        .user-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .user-header {
            background-color: #eef2ff;
            border-bottom: 1px solid #cbd5e1;
            padding: 7px 10px;
        }
        .user-name {
            font-size: 11.5px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .user-details {
            font-size: 9.5px;
            color: #475569;
            margin-top: 2px;
        }
        .user-depts {
            font-size: 9px;
            color: #334155;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-active {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-dept {
            background-color: #e0e7ff;
            color: #3730a3;
            margin-right: 4px;
        }
        .badge-dept-expired {
            background-color: #fee2e2;
            color: #991b1b;
            margin-right: 4px;
        }
        .permissions-table {
            width: 100%;
            border-collapse: collapse;
        }
        .permissions-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border-bottom: 1px solid #cbd5e1;
        }
        .permissions-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }
        .permissions-table tr:last-child td {
            border-bottom: none;
        }
        .no-permissions {
            padding: 8px 10px;
            color: #64748b;
            font-style: italic;
            font-size: 9.5px;
        }
        .empty-results {
            padding: 20px;
            text-align: center;
            border: 1px dashed #cbd5e1;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">Raport Dostępu i Uprawnień Użytkowników</div>
        <div class="report-meta">
            Wygenerowano: {{ $generatedAt }}
            &nbsp;|&nbsp; Wygenerował(a): {{ $generatedBy->name }} ({{ $generatedBy->email }})
            &nbsp;|&nbsp; Liczba wyszukanych użytkowników: <strong>{{ $users->count() }}</strong>
        </div>
    </div>

    <div class="filters-box">
        <div class="section-title">Zastosowane filtry wyszukiwania</div>
        <table class="filters-table">
            <tr>
                <td class="filter-label">Szukana fraza:</td>
                <td class="filter-value {{ $filterSummary['search_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['search'] }}
                </td>
                <td class="filter-label">Status konta:</td>
                <td class="filter-value {{ $filterSummary['user_status_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['user_status'] }}
                </td>
            </tr>
            <tr>
                <td class="filter-label">Wydział:</td>
                <td class="filter-value {{ $filterSummary['dept_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['dept'] }}
                </td>
                <td class="filter-label">Status wydziału:</td>
                <td class="filter-value {{ $filterSummary['dept_status_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['dept_status'] }}
                </td>
            </tr>
            <tr>
                <td class="filter-label">Okres wydziału:</td>
                <td class="filter-value {{ $filterSummary['dept_dates_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['dept_dates'] }}
                </td>
                <td class="filter-label">Moduł:</td>
                <td class="filter-value {{ $filterSummary['module_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['module'] }}
                </td>
            </tr>
            <tr>
                <td class="filter-label">Status modułu:</td>
                <td class="filter-value {{ $filterSummary['module_status_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['module_status'] }}
                </td>
                <td class="filter-label">Okres uprawnienia:</td>
                <td class="filter-value {{ $filterSummary['module_dates_active'] ? 'filter-active' : '' }}">
                    {{ $filterSummary['module_dates'] }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title" style="margin-bottom: 10px;">
        Wyszukani użytkownicy i ich uprawnienia ({{ $users->count() }})
    </div>

    @forelse($users as $user)
        @php
            $userAccesses = $user->filtered_accesses ?? $user->pAccesses;
        @endphp
        <div class="user-card">
            <div class="user-header">
                <div class="user-name">
                    {{ $user->name }}
                    @if($user->is_active)
                        <span class="badge badge-active">Konto aktywne</span>
                    @else
                        <span class="badge badge-inactive">Konto nieaktywne</span>
                    @endif
                </div>
                <div class="user-details">
                    E-mail: <strong>{{ $user->email }}</strong>
                    &nbsp;|&nbsp; Rola: <strong>{{ ucfirst($user->role) }}</strong>
                    &nbsp;|&nbsp; Liczba wykazanych uprawnień: <strong>{{ $userAccesses->count() }}</strong>
                </div>
                <div class="user-depts">
                    <strong>Wydziały:</strong>
                    @if($user->departments && $user->departments->isNotEmpty())
                        @foreach($user->sortedDepartments as $dept)
                            @php
                                $isExpired = $dept->pivot->do && $dept->pivot->do < date('Y-m-d');
                            @endphp
                            <span class="badge {{ $isExpired ? 'badge-dept-expired' : 'badge-dept' }}">
                                {{ $dept->Nazwa }}
                                (od: {{ $dept->pivot->od ?: '∞' }}, do: {{ $dept->pivot->do ?: '∞' }}{{ $isExpired ? ' - wygasły' : '' }})
                            </span>
                        @endforeach
                    @else
                        <span style="color: #64748b; font-style: italic;">Brak przypisanego wydziału</span>
                    @endif
                </div>
            </div>

            @if($userAccesses && $userAccesses->isNotEmpty())
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th style="width: 28%;">Moduł</th>
                            <th style="width: 14%;">Operacja</th>
                            <th style="width: 20%;">Wydział w okresie uprawnienia</th>
                            <th style="width: 14%;">Login / Uwagi</th>
                            <th style="width: 8%;">Status</th>
                            <th style="width: 8%;">Ważne od</th>
                            <th style="width: 8%;">Ważne do</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($userAccesses as $access)
                            @php
                                $isValid = $access->isValid();
                                $matchingDepts = $access->getMatchingDepartments($user);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $access->modul ? $access->modul->full_path : '—' }}</strong>
                                </td>
                                <td>{{ $access->operacja->nazwa ?? '—' }}</td>
                                <td>
                                    @if($matchingDepts->isNotEmpty())
                                        @foreach($matchingDepts as $mDept)
                                            <div>
                                                {{ $mDept->Nazwa }}
                                                @if($mDept->pivot->od || $mDept->pivot->do)
                                                    <span style="color: #64748b; font-size: 8px;">
                                                        ({{ $mDept->pivot->od ?: '∞' }} – {{ $mDept->pivot->do ?: '∞' }})
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @else
                                        <span style="color: #94a3b8; font-style: italic;">Brak</span>
                                    @endif
                                </td>
                                <td>
                                    @if($access->login)
                                        <div>Login: <strong>{{ $access->login }}</strong></div>
                                    @endif
                                    @if($access->uwagi)
                                        <div style="color: #475569; font-size: 8.5px;">{{ $access->uwagi }}</div>
                                    @endif
                                    @if(!$access->login && !$access->uwagi)
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isValid)
                                        <span class="badge badge-active">Aktywny</span>
                                    @else
                                        <span class="badge badge-inactive">Nieaktywny</span>
                                    @endif
                                </td>
                                <td>{{ $access->valid_from ? $access->valid_from->format('Y-m-d') : '∞' }}</td>
                                <td>{{ $access->valid_to ? $access->valid_to->format('Y-m-d') : '∞' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-permissions">
                    Brak przypisanych uprawnień spełniających kryteria.
                </div>
            @endif
        </div>
    @empty
        <div class="empty-results">
            Brak użytkowników spełniających podane kryteria filtrowania.
        </div>
    @endforelse
</body>
</html>

