@extends('Backend.layouts.app')
@section('title', 'Edytuj Dokument')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Edytuj dokument</h1>
                <p>Zaktualizuj dane dokumentu lub podmień plik.</p>
            </div>
            <a href="{{ route('documents.index') }}" style="color: var(--text-muted); text-decoration: none;">← Powrót do listy</a>
        </div>

        <div style="max-width: 560px;">
            <div class="card" style="padding: 1.75rem 2rem;">
                @if($errors->any())
                    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('documents.update', $document) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="nazwa" class="form-label">Nazwa dokumentu <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="nazwa" id="nazwa" class="form-control" placeholder="np. Instrukcja korzystania z modułu" value="{{ old('nazwa', $document->nazwa) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="p_modul_id" class="form-label">Przypisz do modułu <span style="color: var(--danger);">*</span></label>
                        <select name="p_modul_id" id="p_modul_id" class="form-control" required>
                            <option value="">-- Wybierz moduł --</option>
                            @foreach($modules as $module)
                                @include('Backend.admin.permissions.modules._option', ['module' => $module, 'depth' => 0, 'selectedId' => old('p_modul_id', $document->p_modul_id)])
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file" class="form-label">Podmień plik (opcjonalnie)</label>
                        <input type="file" name="file" id="file" class="form-control" style="padding: 0.5rem;">
                        
                        <div style="margin-top: 0.6rem; background: rgba(255,255,255,0.03); border: 1px dashed var(--border); border-radius: 8px; padding: 0.6rem 0.8rem; font-size: 0.85rem; color: var(--text-muted);">
                            <strong>Obecny plik:</strong> 📄 {{ $document->original_filename }}
                        </div>
                        
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.4rem;">
                            Wybierz nowy plik tylko wtedy, gdy chcesz zastąpić obecny. Maksymalny rozmiar pliku: 10 MB.
                        </p>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary" style="width: 100%;">💾 Zapisz zmiany</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
