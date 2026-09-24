@extends('panel.layouts.app')

@section('title', 'Créer un type de document')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Créer un type de document</h1>
            <p class="text-muted">Ajout d'un nouveau type de document</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('admin.bibliotheque.type-documents.store') }}">
            @csrf

            <div class="mb-3">
                <label>Nom</label>
                <input type="text"
                       name="nom"
                       class="form-control @error('nom') is-invalid @enderror"
                       value="{{ old('nom') }}"
                       placeholder="Ex: Cours, Exercice, Évaluation..."
                       required>
                @error('nom')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label>Sigle</label>
                <input type="text"
                       name="sigle"
                       class="form-control @error('sigle') is-invalid @enderror"
                       value="{{ old('sigle') }}"
                       placeholder="Ex: COURS, EXERC, EVAL..."
                       maxlength="25">
                @error('sigle')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.bibliotheque.type-documents.index') }}"
                   class="btn btn-light">
                    Annuler
                </a>
                <button type="submit"
                        class="btn btn-primary">
                    Enregistrer
                </button>
            </div>

        </form>

    </div>

</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assetBiblio/css/bibliotheque.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assetBiblio/js/bibliotheque.js') }}"></script>
@endpush
