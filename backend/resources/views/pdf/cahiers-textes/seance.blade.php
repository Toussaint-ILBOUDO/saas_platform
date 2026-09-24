@extends('pdf.cahiers-textes.layouts.base')

@section('content')

<div class="section">
    <h1>Cahier de texte - Séance</h1>

    <table class="table">
        <tr>
            <th>Date</th>
            <td>{{ $cahier->date_seance->format('d/m/Y') }}</td>
        </tr>

        <tr>
            <th>Élève</th>
            <td>
                {{ $cahier->affectation->contrat->eleve->user->prenom }}
                {{ $cahier->affectation->contrat->eleve->user->nom }}
            </td>
        </tr>

        <tr>
            <th>Matière</th>
            <td>{{ $cahier->affectation->matiere->nom }}</td>
        </tr>

        <tr>
            <th>Enseignant</th>
            <td>
                {{ $cahier->affectation->enseignant->user->prenom }}
                {{ $cahier->affectation->enseignant->user->nom }}
            </td>
        </tr>

        <tr>
            <th>Horaire</th>
            <td>{{ substr($cahier->heure_debut,0,5) }} - {{ substr($cahier->heure_fin,0,5) }}</td>
        </tr>

        <tr>
            <th>Durée</th>
            <td>{{ $cahier->duree_heures }} h</td>
        </tr>
    </table>
</div>

<div class="section panel">
    <h2>Contenu du cours</h2>
    <p>{{ $cahier->contenu_cours }}</p>
</div>

<div class="section panel">
    <h2>Objectifs atteints</h2>
    <p>{{ $cahier->objectifs_atteints ?? '-' }}</p>
</div>

<div class="section panel">
    <h2>Observations</h2>
    <p>{{ $cahier->observations ?? '-' }}</p>
</div>

@endsection