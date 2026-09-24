@extends('pdf.cahiers-textes.layouts.base')

@section('content')

<div class="section">

    <h1>Historique des cahiers de texte</h1>

    <p>
        Élève :
        <strong>{{ $eleve->user->prenom }} {{ $eleve->user->nom }}</strong>
    </p>

    <p>
        Période : {{ $dateDebut ?? '-' }} → {{ $dateFin ?? '-' }}
    </p>

</div>

<div class="section">

    <h2>Liste des séances</h2>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Matière</th>
                <th>Enseignant</th>
                <th>Durée</th>
            </tr>
        </thead>

        <tbody>
        @foreach($cahiers as $cahier)
            <tr>
                <td>{{ $cahier->date_seance->format('d/m/Y') }}</td>
                <td>{{ $cahier->affectation->matiere->nom }}</td>
                <td>
                    {{ $cahier->affectation->enseignant->user->prenom }}
                    {{ $cahier->affectation->enseignant->user->nom }}
                </td>
                <td>{{ $cahier->duree_heures }} h</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>

<div class="section">

    <h2>Détail des séances</h2>

    @foreach($cahiers as $cahier)
        <div class="panel">
            <strong>{{ $cahier->date_seance->format('d/m/Y') }}</strong>
            <p>{{ $cahier->contenu_cours }}</p>
        </div>
    @endforeach

</div>

@endsection