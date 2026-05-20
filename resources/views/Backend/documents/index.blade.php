@extends('Backend.layouts.app')
@section('title', 'Dokumenty')

@section('content')
    <!-- Sidebar -->
    @if($role === 'admin')
        @include('Backend.admin._sidebar')
    @elseif($role === 'mod')
        @include('Backend.mod._sidebar')
    @elseif($role === 'user')
        @include('Backend.user._sidebar')
    @endif

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dokumenty i Instrukcje</h1>
                <p>Dokumentacja przypisana do modułów systemowych.</p>
            </div>
            
            <div class="header-actions">
                @if($role === 'admin')
                    <a href="{{ route('documents.create') }}" class="btn-add-user">
                        <span style="font-size: 1.2rem; line-height: 1;">+</span> Dodaj dokument
                    </a>
                @endif
                <div class="status-badge" style="text-transform: capitalize;">
                    Rola: {{ $role }}
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                Lista Dokumentów
            </div>
            <div class="activity-list" style="padding: 1rem; overflow-x: auto;">
                @if($documents->count() > 0)
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); text-align: left; color: var(--text-muted); font-size: 0.85rem;">
                                <th style="padding: 1rem 0.75rem;">Nazwa Dokumentu</th>
                                <th style="padding: 1rem 0.75rem;">Przypisany Moduł</th>
                                <th style="padding: 1rem 0.75rem;">Nazwa Pliku</th>
                                <th style="padding: 1rem 0.75rem;">Data Dodania</th>
                                <th style="padding: 1rem 0.75rem; text-align: right;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $document)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.02)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 1rem 0.75rem; font-weight: 600; color: var(--text-main);">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="font-size: 1.25rem;">📄</span>
                                            {{ $document->nazwa }}
                                        </div>
                                    </td>
                                    <td style="padding: 1rem 0.75rem;">
                                        <span style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; display: inline-block;">
                                            {{ $document->module->nazwa ?? 'Brak modułu' }}
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 0.75rem; color: var(--text-muted); font-size: 0.875rem;">
                                        {{ $document->original_filename }}
                                    </td>
                                    <td style="padding: 1rem 0.75rem; color: var(--text-muted); font-size: 0.875rem;">
                                        {{ $document->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td style="padding: 1rem 0.75rem; text-align: right;">
                                        <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                            <a href="{{ route('documents.download', $document) }}" class="btn-table-action" style="color: var(--success); border-color: rgba(16, 185, 129, 0.25);">
                                                📥 Pobierz
                                            </a>
                                            
                                            @if($role === 'admin')
                                                <a href="{{ route('documents.edit', $document) }}" class="btn-table-action">
                                                    ✏️ Edytuj
                                                </a>
                                                <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć ten dokument?')" style="margin: 0; display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-table-danger">
                                                        🗑️ Usuń
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align: center; color: var(--text-muted); padding: 4rem 0;">
                        <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📂</span>
                        <p style="font-size: 1.1rem; font-weight: 500;">Brak dostępnych dokumentów</p>
                        <p style="font-size: 0.9rem; margin-top: 0.25rem;">W systemie nie dodano jeszcze żadnych dokumentów przypisanych do modułów.</p>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection
