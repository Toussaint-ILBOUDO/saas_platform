@extends('panel.layouts.app')

@section('title', 'Modifier — ' . $typeDocument->nom)

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div>
            <h1>Modifier le type de document</h1>
            <p class="text-muted">Mise à jour des informations</p>
        </div>
    </div>

    <div class="panel">

        <form method="POST" action="{{ route('admin.bibliotheque.type-documents.update', $typeDocument) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nom</label>
                <input type="text"
                       name="nom"
                       value="{{ old('nom', $typeDocument->nom) }}"
                       class="form-control @error('nom') is-invalid @enderror"
                       required>
                @error('nom')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label>Sigle</label>
                <input type="text"
                       name="sigle"
                       value="{{ old('sigle', $typeDocument->sigle) }}"
                       class="form-control @error('sigle') is-invalid @enderror"
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
                    Mettre à jour
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
