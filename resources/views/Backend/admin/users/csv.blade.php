@extends('Backend.layouts.app')
@section('title', 'Import / Eksport CSV - Admin')

@section('content')
    @include('Backend.admin._sidebar')

    <!-- Main Content -->
    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Import / Eksport CSV</h1>
                <p>Zarządzaj bazą użytkowników za pomocą plików CSV.</p>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="{{ route('users.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.4rem; transition: color 0.2s;"
                   onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
                    ← Powrót do listy
                </a>
                <div class="status-badge">
                    Zalogowano jako Administrator
                </div>
            </div>
        </div>

        <div style="max-width: 800px; display: grid; gap: 1.5rem;">
            <!-- Alert success / error -->
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom: 0;">
                    {!! nl2br(e(session('success'))) !!}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" style="margin-bottom: 0;">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Warning logs from import -->
            @if(session('warnings'))
                <div class="alert alert-danger" style="margin-bottom: 0; background: rgba(239, 68, 68, 0.08); border-color: rgba(239, 68, 68, 0.2);">
                    <h4 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--danger); font-weight: 600;">Ostrzeżenia podczas importu:</h4>
                    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.82rem; color: var(--text-main); line-height: 1.5;">
                        @foreach(session('warnings') as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Sekcja Export i Szablon -->
                <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; min-height: 250px;">
                    <div>
                        <h2 style="font-size: 1.05rem; font-weight: 600; color: var(--text-main); margin-top: 0; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            📥 Eksport i Szablony
                        </h2>
                        <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 1.25rem;">
                            Pobierz wzór pliku CSV, aby prawidłowo sformatować dane przed importem, lub wyeksportuj wszystkich aktualnych użytkowników.
                        </p>
                    </div>

                    <div style="display: grid; gap: 0.75rem; margin-top: auto;">
                        <a href="{{ route('users.csv.pattern') }}" class="btn btn-secondary" style="text-align: center; text-decoration: none; display: block; font-size: 0.88rem; padding: 0.7rem;">
                            Pobierz wzór CSV
                        </a>
                        <a href="{{ route('users.csv.export') }}" class="btn btn-primary" style="text-align: center; text-decoration: none; display: block; font-size: 0.88rem; padding: 0.7rem;">
                            Eksportuj użytkowników do CSV
                        </a>
                    </div>
                </div>

                <!-- Sekcja Import -->
                <div class="card" style="min-height: 250px;">
                    <h2 style="font-size: 1.05rem; font-weight: 600; color: var(--text-main); margin-top: 0; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        📤 Import danych z CSV
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.5; margin-bottom: 1.25rem;">
                        Wybierz plik CSV z użytkownikami do zaimportowania. Dla każdego nowego użytkownika zostanie wygenerowane bezpieczne hasło, a szczegóły hasła zostaną zapisane w logach aktywności.
                    </p>

                    <form action="{{ route('users.csv.import') }}" method="POST" enctype="multipart/form-data" style="display: grid; gap: 1rem;">
                        @csrf
                        <div style="border: 2px dashed var(--border); border-radius: 8px; padding: 1.5rem 1rem; text-align: center; background: rgba(0,0,0,0.1); cursor: pointer; transition: border-color 0.2s;"
                             onclick="document.getElementById('csv_file').click()"
                             onmouseover="this.style.borderColor='var(--primary)'"
                             onmouseout="this.style.borderColor='var(--border)'">
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" style="display: none;" onchange="updateFileName(this)">
                            <div style="font-size: 1.5rem; margin-bottom: 0.4rem;">📄</div>
                            <div id="file-label" style="font-size: 0.82rem; color: var(--text-muted); font-weight: 500;">
                                Kliknij tutaj, aby wybrać plik CSV
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="font-size: 0.88rem; padding: 0.7rem;">
                            Rozpocznij import danych
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informacje pomocnicze -->
            <div class="card" style="padding: 1.25rem 1.5rem;">
                <h3 style="font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-top: 0; margin-bottom: 0.5rem;">
                    💡 Instrukcja i format pliku CSV:
                </h3>
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; display: grid; gap: 0.25rem;">
                    <li>Plik musi być zakodowany w formacie <strong>UTF-8</strong> (wzór CSV zawiera odpowiedni marker BOM, zapobiegający problemom z kodowaniem w Excelu).</li>
                    <li>Wymagane nagłówki kolumn: <code>imię_i_nazwisko</code>, <code>email</code>, <code>rola</code> (admin / mod / user / none), <code>status</code> (aktywny / nieaktywny).</li>
                    <li>Opcjonalna kolumna <code>wydziały</code> (lub <code>wydzialy</code>): nazwy wydziałów oddzielone przecinkami (np. <code>Wydział IT, Wydział Finansów</code>).</li>
                    <li>Jeśli wydział podany w CSV nie istnieje jeszcze w bazie, zostanie on automatycznie utworzony.</li>
                </ul>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    function updateFileName(input) {
        const label = document.getElementById('file-label');
        if (input.files && input.files.length > 0) {
            label.textContent = 'Wybrany plik: ' + input.files[0].name;
            label.style.color = 'var(--text-main)';
        } else {
            label.textContent = 'Kliknij tutaj, aby wybrać plik CSV';
            label.style.color = 'var(--text-muted)';
        }
    }
</script>
@endpush
