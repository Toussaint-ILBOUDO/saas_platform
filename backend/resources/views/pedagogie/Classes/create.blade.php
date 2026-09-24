@extends('panel.layouts.app')

@section('title', 'Créer une classe')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-plus-circle"></i>
            </div>
            <div>
                <h1 class="mb-0">Nouvelle classe</h1>
                <p class="text-muted mb-0">Ajouter une nouvelle classe</p>
            </div>
        </div>
    </div>

    <div class="panel">

        <form action="{{ route('classes.store') }}" method="POST">
            @csrf

            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label">Nom de la classe</label>
                    <input type="text"
                           name="nom"
                           class="form-control"
                           value="{{ old('nom') }}"
                           placeholder="Ex: Terminale D">

                    @error('nom')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sigle</label>
                    <input type="text"
                           name="sigle"
                           class="form-control"
                           value="{{ old('sigle') }}"
                           placeholder="Ex: TD">

                    @error('sigle')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">

                <a href="{{ route('classes.index') }}" class="btn btn-light">
                    Annuler
                </a>

                <button class="btn btn-primary">
                    <i class="bi bi-check2"></i>
                    Enregistrer
                </button>

            </div>

        </form>

    </div>

</div>

@endsection