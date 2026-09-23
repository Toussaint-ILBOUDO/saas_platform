@extends('panel.layouts.app')

@section('title', 'Modifier la classe')

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-pencil-square"></i>
            </div>
            <div>
                <h1 class="mb-0">Modifier la classe</h1>
                <p class="text-muted mb-0">Mise à jour des informations</p>
            </div>
        </div>
    </div>

    <div class="panel">

        <form action="{{ route('classes.update', $classe) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label">Nom de la classe</label>
                    <input type="text"
                           name="nom"
                           class="form-control"
                           value="{{ old('nom', $classe->nom) }}">

                    @error('nom')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Sigle</label>
                    <input type="text"
                           name="sigle"
                           class="form-control"
                           value="{{ old('sigle', $classe->sigle) }}">

                    @error('sigle')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">

                <a href="{{ route('classes.index') }}" class="btn btn-light">
                    Retour
                </a>

                <button class="btn btn-primary">
                    <i class="bi bi-save"></i>
                    Mettre à jour
                </button>

            </div>

        </form>

    </div>

</div>

@endsection