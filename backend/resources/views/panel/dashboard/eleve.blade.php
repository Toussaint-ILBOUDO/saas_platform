@extends('panel.layouts.app')

@section('title', 'Dashboard Élève')

@section('content')

<div class="container-fluid px-3 px-lg-4 py-4">

    <h1 class="mb-1">Dashboard Élève</h1>

    <p class="text-muted mb-4">
        Bienvenue chez K'Educ — vos cours, séances et objectifs en un coup d'œil.
    </p>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes cours</h5>
                <p class="text-muted mb-2">Vos contrats de cours en cours</p>
                <a href="{{ route('mes-cours.index') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Planning</h5>
                <p class="text-muted mb-2">Vos séances de la semaine</p>
                <a href="{{ route('planning.index') }}">Consulter</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Évaluations</h5>
                <p class="text-muted mb-2">Vos appréciations de cours</p>
                <a href="{{ route('evaluations.index') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Objectifs pédagogiques</h5>
                <p class="text-muted mb-2">Les objectifs définis pour vous</p>
                <a href="{{ route('objectifs-pedagogiques.index') }}">Consulter</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Cahiers de textes</h5>
                <p class="text-muted mb-2">Le contenu de vos séances</p>
                <a href="{{ route('cahiers-textes.index') }}">Consulter</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Bibliothèque numérique</h5>
                <p class="text-muted mb-2">Explorez les documents de l'école</p>
                <a href="{{ route('bibliothequepub.index') }}">Explorer</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes favoris</h5>
                <p class="text-muted mb-2">Vos documents préférés</p>
                <a href="{{ route('bibliotheque.favoris') }}">Voir</a>
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
                <h5>Mes témoignages</h5>
                <p class="text-muted mb-2">Vos avis publiés</p>
                <a href="{{ route('temoignages.mes.index') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Ma fiche</h5>
                <p class="text-muted mb-2">Votre dossier élève</p>
                <a href="{{ route('eleves.fiche', auth()->user()->eleve) }}">Consulter</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Mes documents</h5>
                <p class="text-muted mb-2">Vos documents partagés</p>
                <a href="{{ route('bibliotheque.index') }}">Voir</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3">
                <h5>Notifications</h5>
                <p class="text-muted mb-2">Vos alertes et messages</p>
                <a href="{{ route('notifications.index') }}">Voir</a>
            </div>
        </div>

    </div>

</div>

@endsection