<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport mensuel</title>

    <style>
        @page {
            margin: 35px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #212529;
        }


        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            margin-top: 10px;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subtitle {
            margin-top: 5px;
            color: #666;
        }


        /* SECTIONS */
        .section {
            margin-top: 20px;
        }

        .section-title {
            padding: 8px;
            background: #f1f3f5;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
        }


        /* INFORMATIONS */
        .info-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }

        .label {
            width: 30%;
            background: #f8f9fa;
            font-weight: bold;
        }


        /* BADGE */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #198754;
            color: white;
            border-radius: 10px;
            font-size: 10px;
        }


        /* CONTENU */
        .content-box {
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #dee2e6;
            line-height: 1.5;
        }


        /* COLONNES */
        .two-columns {
            width: 100%;
        }

        .column {
            width: 50%;
            padding-right: 15px;
            vertical-align: top;
        }


        /* SIGNATURE */
        .signature {
            width: 100%;
            margin-top: 50px;
        }

        .signature td {
            width: 50%;
            text-align: center;
        }

        .line {
            margin-top: 45px;
            border-top: 1px solid #333;
        }


        /* FOOTER */
        .footer {
            position: fixed;
            bottom: -20px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>


<body>

    <div class="header">

        <div class="logo">
            {{ config('app.name') }}
        </div>

        <div class="title">
            Rapport mensuel pédagogique
        </div>

        <div class="subtitle">
            Synthèse des activités d'enseignement
        </div>

    </div>
    <div class="section">

    <div class="section-title">
        Informations générales
    </div>

    <table class="info-table">

        <tr>
            <td class="label">Élève</td>
            <td>
                {{ $rapport->contratCours->eleve->user->prenom }}
                {{ $rapport->contratCours->eleve->user->nom }}
            </td>
        </tr>

        <tr>
            <td class="label">Matière</td>
            <td>
                {{ $rapport->contratCours->affectations->firstWhere('enseignant_id', $rapport->enseignant_id)?->matiere?->nom ?? '—' }}
            </td>
        </tr>

        <tr>
            <td class="label">Enseignant</td>
            <td>
                {{ $rapport->enseignant->user->prenom }}
                {{ $rapport->enseignant->user->nom }}
            </td>
        </tr>

        <tr>
            <td class="label">Période</td>
            <td>
                {{ $rapport->periode->label ?? 'Période comptable' }}<br>
                Du {{ $rapport->periode->date_debut->format('d/m/Y') }}
                au {{ $rapport->periode->date_fin->format('d/m/Y') }}
            </td>
        </tr>

        <tr>
            <td class="label">Volume horaire réalisé</td>
            <td>
                <strong>
                    {{ number_format($rapport->volume_horaire_cumule, 1) }} heures
                </strong>
            </td>
        </tr>

        <tr>
            <td class="label">Statut</td>
            <td>
                <span class="badge">
                    {{ ucfirst($rapport->statut) }}
                </span>
            </td>
        </tr>

    </table>

</div>



<div class="section">

    <div class="section-title">
        Bilan des activités réalisées
    </div>

    <div class="content-box">
        {!! nl2br(e($rapport->bilan_activites)) !!}
    </div>

</div>



<div class="section">

    <div class="section-title">
        Évaluation pédagogique
    </div>

    <table class="two-columns">

        <tr>

            <td class="column">

                <strong>
                    Points notables sur la matière
                </strong>

                <div class="content-box">
                    {{ $rapport->point_notes_matieres ?: 'Aucune information.' }}
                </div>

            </td>


            <td class="column">

                <strong>
                    Autres matières
                </strong>

                <div class="content-box">
                    {{ $rapport->point_notes_autres_matieres ?: 'Aucune information.' }}
                </div>

            </td>

        </tr>

    </table>

</div>




<div class="section">

    <div class="section-title">
        Analyse et accompagnement
    </div>


    <table class="info-table">

        <tr>
            <td class="label">
                Difficultés rencontrées
            </td>

            <td>
                {{ $rapport->difficultes_rencontrees ?: 'Aucune difficulté signalée.' }}
            </td>
        </tr>


        <tr>
            <td class="label">
                Solutions proposées
            </td>

            <td>
                {{ $rapport->solutions_trouvees ?: 'Aucune solution renseignée.' }}
            </td>
        </tr>


        <tr>
            <td class="label">
                Attentes parents / élève
            </td>

            <td>
                {{ $rapport->attentes_parents_eleve ?: 'Aucune attente renseignée.' }}
            </td>
        </tr>


        <tr>
            <td class="label">
                Attentes administration
            </td>

            <td>
                {{ $rapport->attentes_administration ?: 'Aucune attente renseignée.' }}
            </td>
        </tr>

    </table>

</div>




<div class="section">

    <div class="section-title">
        Appréciation générale
    </div>

    <div class="content-box">
        {{ $rapport->appreciation_evolution ?: 'Aucune appréciation renseignée.' }}
    </div>

</div>




<div class="section">

    <div class="section-title">
        Observations
    </div>

    <div class="content-box">
        {{ $rapport->observations ?: 'Aucune observation.' }}
    </div>

</div>




<table class="signature">

    <tr>

        <td>
            Enseignant
            <div class="line"></div>
        </td>

        <td>
            Administration
            <div class="line"></div>
        </td>

    </tr>

</table>




<div class="footer">

    Document généré automatiquement le
    {{ now()->format('d/m/Y à H:i') }}

</div>


</body>
</html>