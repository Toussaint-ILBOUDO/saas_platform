@extends('panel.layouts.app')

@section('title', 'Dashboard Enseignant')

@section('content')

<div class="container-fluid px-3 px-lg-4 py-4">

    <h1 class="mb-4">Dashboard Enseignant</h1>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes cours</h5>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Cahier de texte</h5>
                <a href="{{ route('cahiers-textes.create.select-eleve') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Actualités</h5>
                <p class="text-muted mb-2">Les dernières nouvelles de l'école</p>
                <a href="{{ route('actualites.internes') }}">Suivre</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Bibliothèque</h5>
                <p class="text-muted mb-2">Les documents de l'école</p>
                <a href="{{ route('bibliothequepub.index') }}">Explorer</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes bulletins de paie</h5>
                <a href="{{ route('mes-bulletins.index') }}">Voir</a>
            </div>
        </div>

    </div>

</div>

@endsection