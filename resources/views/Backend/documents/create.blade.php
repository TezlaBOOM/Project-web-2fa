@extends('Backend.layouts.app')
@section('title', 'Dodaj Dokument')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dodaj nowy dokument</h1>
                <p>Wgraj instrukcję lub materiał i przypisz go do wybranego modułu.</p>
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

                <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="nazwa" class="form-label">Nazwa dokumentu <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="nazwa" id="nazwa" class="form-control" placeholder="np. Instrukcja korzystania z modułu" value="{{ old('nazwa') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="p_modul_id" class="form-label">Przypisz do modułu <span style="color: var(--danger);">*</span></label>
                        <select name="p_modul_id" id="p_modul_id" class="form-control" required>
                            <option value="">-- Wybierz moduł --</option>
                            @foreach($modules as $module)
                                @include('Backend.admin.permissions.modules._option', ['module' => $module, 'depth' => 0, 'selectedId' => old('p_modul_id')])
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file" class="form-label">Wybierz plik <span style="color: var(--danger);">*</span></label>
                        <input type="file" name="file" id="file" class="form-control" style="padding: 0.5rem;" required>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.4rem;">
                            Dozwolone są dokumenty (PDF, DOC, XLS, TXT itp.) oraz obrazy. Maksymalny rozmiar pliku: 10 MB.
                        </p>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary" style="width: 100%;">💾 Zapisz dokument</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
