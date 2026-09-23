@extends('panel.layouts.app')

@section('title', 'Contester le bulletin ' . $bulletin->numero)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <h1 class="mb-0">Contester le bulletin {{ $bulletin->numero }}</h1>
                <p class="text-muted mb-0">
                    {{ $bulletin->periode?->label }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('mes-bulletins.show', $bulletin) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <h5 class="mb-0">Motif de la contestation</h5>
        </div>
        <div class="panel-body">
            <form method="POST" action="{{ route('mes-bulletins.contester', $bulletin) }}">
                @csrf

                <div class="mb-3">
                    <label for="commentaire_enseignant" class="form-label">
                        Decrivez le motif de votre contestation
                    </label>
                    <textarea name="commentaire_enseignant"
                              id="commentaire_enseignant"
                              class="form-control"
                              rows="5"
                              maxlength="1000"
                              required
                              placeholder="Expliquez pourquoi vous contestez ce bulletin...">{{ old('commentaire_enseignant', $bulletin->commentaire_enseignant) }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-send"></i> Soumettre la contestation
                    </button>
                    <a href="{{ route('mes-bulletins.show', $bulletin) }}" class="btn btn-light">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
