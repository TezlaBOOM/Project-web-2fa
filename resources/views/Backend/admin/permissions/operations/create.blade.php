@extends('Backend.layouts.app')
@section('title', 'Dodaj Operację')

@section('content')
    @include('Backend.admin._sidebar')

    <main class="main-content">
        <div class="header-bar">
            <div class="user-greeting">
                <h1>Dodaj nową operację</h1>
            </div>
            <a href="{{ route('operations.index') }}" style="color: var(--text-muted); text-decoration: none;">← Powrót do listy</a>
        </div>

        <div style="max-width: 600px;">
            <div class="card" style="padding: 2rem;">
                <form action="{{ route('operations.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label for="nazwa" class="form-label">Nazwa Operacji <span style="color: var(--danger);">*</span></label>
                        <input type="text" name="nazwa" id="nazwa" class="form-control" value="{{ old('nazwa') }}" required>
                        @error('nazwa') <p style="color: var(--danger); font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</p> @enderror
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-primary">Dodaj operację</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
