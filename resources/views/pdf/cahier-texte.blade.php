<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <title>
        Cahier de Texte
    </title>

    <style>

        body{
            font-family: sans-serif;
            font-size: 12px;
        }

        h1{
            text-align:center;
        }

        table{
            width:100%;
            border-collapse: collapse;
        }

        td,th{
            border:1px solid #000;
            padding:8px;
        }

        .section{
            margin-top:20px;
        }

    </style>

</head>

<body>

<h1>
    CAHIER DE TEXTE
</h1>

<div class="section">

    <table>

        <tr>
            <th>Élève</th>
            <td>
                {{ $cahier->affectation->contrat->eleve->user->name }}
            </td>
        </tr>

        <tr>
            <th>Enseignant</th>
            <td>
                {{ $cahier->affectation->enseignant->user->name }}
            </td>
        </tr>

        <tr>
            <th>Matière</th>
            <td>
                {{ $cahier->affectation->matiere->nom }}
            </td>
        </tr>

        <tr>
            <th>Date</th>
            <td>
                {{ $cahier->date_seance->format('d/m/Y') }}
            </td>
        </tr>

        <tr>
            <th>Heure</th>
            <td>
                {{ $cahier->heure_debut }}
                -
                {{ $cahier->heure_fin }}
            </td>
        </tr>

        <tr>
            <th>Durée</th>
            <td>
                {{ $cahier->duree_heures }} h
            </td>
        </tr>

    </table>

</div>

<div class="section">

    <h3>
        Contenu du cours
    </h3>

    <p>
        {{ $cahier->contenu_cours }}
    </p>

</div>

@if($cahier->objectifs_atteints)

<div class="section">

    <h3>
        Objectifs atteints
    </h3>

    <p>
        {{ $cahier->objectifs_atteints }}
    </p>

</div>

@endif

@if($cahier->observations)

<div class="section">

    <h3>
        Observations
    </h3>

    <p>
        {{ $cahier->observations }}
    </p>

</div>

@endif

</body>
</html>
