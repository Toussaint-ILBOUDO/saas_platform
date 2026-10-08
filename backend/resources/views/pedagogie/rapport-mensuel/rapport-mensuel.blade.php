<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport mensuel {{ $numero ?? '' }}</title>

    <style>
        /* =============================================
         * DomPDF safe : tables uniquement (pas de
         * flexbox/grid), couleurs littérales (pas de
         * variables CSS), en-tête/pied en position
         * fixed — DomPDF les recopie sur CHAQUE page.
         * ============================================= */

        @page {
            margin: 116px 42px 84px 42px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11.4px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }


        /* ---------- EN-TÊTE FIXE (chaque page) ---------- */

        .entete {
            position: fixed;
            top: -98px;
            left: 0;
            right: 0;
            height: 86px;
            border-bottom: 2.5px solid #12305e;
        }

        .entete table {
            width: 100%;
            border-collapse: collapse;
        }

        .entete td {
            vertical-align: middle;
            padding: 0;
        }

        .entete .cell-logo {
            width: 92px;
            padding-right: 15px !important;
        }

        .entete .cell-logo img {
            max-height: 70px;
            max-width: 76px;
        }

        .entete .monogramme {
            width: 56px;
            height: 56px;
            line-height: 56px;
            background: #12305e;
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        .entete .nom-cabinet {
            font-size: 16.5px;
            font-weight: bold;
            color: #12305e;
            letter-spacing: 0.3px;
        }

        .entete .slogan {
            font-size: 11px;
            font-style: italic;
            color: #64748b;
            margin-top: 3px;
        }

        .entete .contacts {
            font-size: 9.8px;
            color: #334155;
            margin-top: 4px;
        }


        /* ---------- BANDEAU DE TITRE ---------- */

        .bande-titre {
            width: 100%;
            background: #12305e;
            color: #ffffff;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .bande-titre td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .bande-titre .titre {
            font-size: 15.5px;
            font-weight: bold;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .bande-titre .sous-titre {
            font-size: 10.5px;
            color: #c7d6ee;
            margin-top: 5px;
        }

        .bande-titre .cell-ref {
            text-align: right;
            width: 180px;
        }

        .bande-titre .ref {
            font-size: 10.8px;
            font-weight: bold;
        }

        .bande-titre .edition {
            font-size: 9.6px;
            color: #c7d6ee;
            margin-top: 4px;
        }


        /* ---------- LIGNE PÉRIODE + STATUT ---------- */

        .ligne-periode {
            width: 100%;
            border-collapse: collapse;
            margin-top: 9px;
        }

        .ligne-periode td {
            background: #eef3fb;
            border: 1px solid #dbe4ef;
            padding: 7px 11px;
            font-size: 11.2px;
        }

        .ligne-periode .cell-statut {
            text-align: right;
            width: 150px;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 9px;
            font-size: 9.6px;
            font-weight: bold;
            color: #ffffff;
            background: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.valide {
            background: #15803d;
        }

        .badge.rejete {
            background: #b91c1c;
        }

        .badge.soumis {
            background: #b45309;
        }


        /* ---------- SECTIONS ---------- */

        .section {
            margin-top: 17px;
        }

        .section-titre {
            background: #eef3fb;
            border-left: 4px solid #1d4e89;
            padding: 7px 11px;
            font-size: 12.4px;
            font-weight: bold;
            color: #12305e;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .section-note {
            font-size: 10.4px;
            color: #64748b;
            font-style: italic;
            margin-top: 6px;
        }


        /* ---------- TABLEAU D'INFORMATIONS ---------- */

        .infos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 9px;
        }

        .infos td {
            border: 1px solid #dbe4ef;
            padding: 7px 10px;
            font-size: 11.2px;
            vertical-align: top;
        }

        .infos .lbl {
            background: #f8fafc;
            font-weight: bold;
            color: #334155;
            width: 17%;
        }

        .infos .val {
            width: 33%;
        }


        /* ---------- TABLEAUX DE DONNÉES ---------- */

        .data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 9px;
            font-size: 10.6px;
        }

        .data thead {
            display: table-header-group;
        }

        .data tr {
            page-break-inside: avoid;
        }

        .data th {
            background: #12305e;
            color: #ffffff;
            padding: 7px 8px;
            text-align: left;
            font-size: 9.8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: 1px solid #12305e;
        }

        .data td {
            border: 1px solid #dbe4ef;
            padding: 6px 8px;
            vertical-align: top;
        }

        .data tr.alt td {
            background: #f8fafc;
        }

        .data .num {
            text-align: right;
            white-space: nowrap;
        }

        .data tr.total td {
            background: #eef3fb;
            font-weight: bold;
            color: #12305e;
            border-top: 2px solid #1d4e89;
        }


        /* ---------- BILAN / CADRE ---------- */

        .cadre {
            border: 1px solid #dbe4ef;
            background: #f8fafc;
            padding: 10px 12px;
            line-height: 1.55;
            margin-top: 8px;
        }


        /* ---------- QUESTIONS / RÉPONSES ---------- */

        .question {
            margin-top: 13px;
            page-break-inside: avoid;
        }

        .question .q {
            font-weight: bold;
            color: #12305e;
        }

        .question .obligatoire {
            color: #dc2626;
            font-weight: normal;
        }

        .question .aide {
            font-size: 10px;
            color: #64748b;
            font-style: italic;
            margin-top: 2px;
        }

        .reponse {
            border: 1px solid #dbe4ef;
            background: #ffffff;
            padding: 9px 11px;
            margin-top: 5px;
            line-height: 1.5;
        }

        .reponse.vide {
            color: #94a3b8;
            font-style: italic;
        }


        /* ---------- SIGNATURES ---------- */

        .mention {
            text-align: center;
            font-size: 11.2px;
            color: #334155;
            margin-top: 30px;
        }

        .signature {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            page-break-inside: avoid;
        }

        .signature td {
            width: 50%;
            text-align: center;
            font-size: 11.3px;
            vertical-align: top;
        }

        .signature .trait {
            border-top: 1px solid #94a3b8;
            margin: 46px 42px 0 42px;
        }

        .signature .role {
            font-weight: bold;
            color: #12305e;
            margin-top: 7px;
        }
    </style>
</head>


<body>

    {{-- En-tête fixe : logo à gauche, nom + slogan + contacts à droite.
         DomPDF le recopie en haut de chaque page. --}}
    <div class="entete">
        <table>
            <tr>
                <td class="cell-logo">
                    @if (! empty($cabinet_logo))
                        <img src="{{ $cabinet_logo }}" alt="Logo">
                    @elseif (! empty($cabinet['nom'] ?? null))
                        <div class="monogramme">
                            {{ strtoupper(mb_substr($cabinet['nom'], 0, 1)) }}
                        </div>
                    @endif
                </td>

                <td>
                    <div class="nom-cabinet">{{ $cabinet_nom }}</div>

                    @if (! empty($cabinet['slogan'] ?? null))
                        <div class="slogan">{{ $cabinet['slogan'] }}</div>
                    @endif

                    <div class="contacts">
                        @php
                            $contacts = [];
                            if (! empty($cabinet['telephone'] ?? null)) {
                                $contacts[] = 'Tél : ' . $cabinet['telephone'];
                            }
                            if (! empty($cabinet['telephone_2'] ?? null)) {
                                $contacts[] = 'Tél 2 : ' . $cabinet['telephone_2'];
                            }
                            if (! empty($cabinet['whatsapp'] ?? null)) {
                                $contacts[] = 'WhatsApp : ' . $cabinet['whatsapp'];
                            }
                            if (! empty($cabinet['email'] ?? null)) {
                                $contacts[] = $cabinet['email'];
                            }
                        @endphp

                        {{ $contacts !== [] ? implode('  ·  ', $contacts) : '' }}

                        @if (! empty($cabinet['adresse'] ?? null))
                            <br>{{ $cabinet['adresse'] }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>


    {{-- Bandeau de titre : document + référence + édition --}}
    <table class="bande-titre">
        <tr>
            <td>
                <div class="titre">Rapport mensuel p&eacute;dagogique</div>
                <div class="sous-titre">
                    Synth&egrave;se des activit&eacute;s d'enseignement
                </div>
            </td>

            <td class="cell-ref">
                <div class="ref">R&eacute;f. {{ $numero ?? '—' }}</div>
                <div class="edition">
                    &Eacute;dit&eacute; le
                    {{ ($date_edition ?? now())->format('d/m/Y à H:i') }}
                </div>
            </td>
        </tr>
    </table>


    {{-- Période + statut --}}
    <table class="ligne-periode">
        <tr>
            <td>
                <strong>P&eacute;riode :</strong>
                {{ $rapport->periode->label ?? 'P&eacute;riode comptable' }}
                @if ($rapport->periode)
                    &mdash; du {{ $rapport->periode->date_debut->format('d/m/Y') }}
                    au {{ $rapport->periode->date_fin->format('d/m/Y') }}
                @endif
            </td>

            <td class="cell-statut">
                <span class="badge {{ $rapport->statut }}">
                    {{ ucfirst($rapport->statut) }}
                </span>
            </td>
        </tr>
    </table>


    {{-- Informations générales --}}
    <div class="section">
        <div class="section-titre">Informations g&eacute;n&eacute;rales</div>

        <table class="infos">
            <tr>
                <td class="lbl">&Eacute;l&egrave;ve</td>
                <td class="val">
                    {{ $eleve?->user?->prenom }} {{ $eleve?->user?->nom ?? '—' }}
                </td>

                <td class="lbl">Classe</td>
                <td class="val">{{ $classe?->nom ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Enseignant</td>
                <td class="val">
                    {{ $professeur?->prenom }} {{ $professeur?->nom ?? '—' }}
                </td>

                <td class="lbl">Type de cours</td>
                <td class="val">{{ $typeCours?->libelle ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Mati&egrave;re(s) confi&eacute;e(s)</td>
                <td class="val" colspan="3">
                    <strong>
                        {{ $matieres->pluck('nom')->implode('  ·  ') ?: '—' }}
                    </strong>
                </td>
            </tr>

            <tr>
                <td class="lbl">S&eacute;ances r&eacute;alis&eacute;es</td>
                <td class="val"><strong>{{ $total_seances }}</strong></td>

                <td class="lbl">Volume horaire r&eacute;alis&eacute;</td>
                <td class="val">
                    <strong>
                        {{ number_format($rapport->volume_horaire_cumule, 1) }} heures
                    </strong>
                </td>
            </tr>
        </table>
    </div>


    {{-- Bilan des activités --}}
    <div class="section">
        <div class="section-titre">Bilan des activit&eacute;s r&eacute;alis&eacute;es</div>

        <div class="cadre">
            {!! nl2br(e($rapport->bilan_activites)) !!}
        </div>

        @if ($cahiers->isNotEmpty())
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Mati&egrave;re</th>
                        <th>Horaires</th>
                        <th>Heures</th>
                        <th>Contenu de la s&eacute;ance</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($cahiers as $cahier)
                        <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                            <td>{{ $cahier->date_seance?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $cahier->affectation?->matiere?->nom ?? '—' }}</td>
                            <td>
                                {{ $cahier->heure_debut ?? '--' }}
                                -
                                {{ $cahier->heure_fin ?? '--' }}
                            </td>
                            <td class="num">
                                {{ number_format((float) $cahier->duree_heures, 1) }}
                            </td>
                            <td>{{ $cahier->contenu_cours }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>


    {{-- Ventilation par matière --}}
    @if ($lignes->isNotEmpty())
        <div class="section">
            <div class="section-titre">
                R&eacute;partition par mati&egrave;re (ventilation)
            </div>

            <div class="section-note">
                Seules vos mati&egrave;res sur ce contrat sont pr&eacute;sent&eacute;es.
            </div>

            <table class="data">
                <thead>
                    <tr>
                        <th>Mati&egrave;re</th>
                        <th>Nombre de s&eacute;ances</th>
                        <th>Nombre d'heures</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lignes as $ligne)
                        <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                            <td>{{ $ligne->affectation?->matiere?->nom ?? '—' }}</td>
                            <td class="num">{{ $ligne->nombre_seances }}</td>
                            <td class="num">
                                {{ number_format((float) $ligne->nombre_heures, 1) }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="total">
                        <td>Total</td>
                        <td class="num">{{ $lignes->sum('nombre_seances') }}</td>
                        <td class="num">
                            {{ number_format((float) $lignes->sum('nombre_heures'), 1) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif


    {{-- Sections administrées + réponses de l'enseignant --}}
    @forelse ($sections as $section)
        <div class="section">
            <div class="section-titre">{{ $section['libelle'] }}</div>

            @if (! empty($section['description']))
                <div class="section-note">{{ $section['description'] }}</div>
            @endif

            @foreach ($section['elements'] as $element)
                <div class="question">
                    <div class="q">
                        {{ $element['libelle'] }}
                        @if ($element['obligatoire'])
                            <span class="obligatoire">&nbsp;&#42;</span>
                        @endif
                    </div>

                    @if (! empty($element['aide']))
                        <div class="aide">{{ $element['aide'] }}</div>
                    @endif

                    @if ($element['reponse'] !== null && trim($element['reponse']) !== '')
                        <div class="reponse">{!! nl2br(e($element['reponse'])) !!}</div>
                    @else
                        <div class="reponse vide">Non renseign&eacute;.</div>
                    @endif
                </div>
            @endforeach
        </div>
    @empty
        <div class="section">
            <div class="section-titre">&Eacute;valuation</div>
            <div class="cadre" style="color:#94a3b8; font-style:italic;">
                Aucune section configur&eacute;e par l'administration.
            </div>
        </div>
    @endforelse


    {{-- Signatures --}}
    <div class="mention">
        @if (! empty($cabinet['adresse'] ?? null))
            Fait &agrave; {{ $cabinet['adresse'] }}, le
        @else
            Fait le
        @endif
        {{ ($date_edition ?? now())->format('d/m/Y') }}
    </div>

    <table class="signature">
        <tr>
            <td>
                <div class="trait"></div>
                <div class="role">L'enseignant</div>
            </td>

            <td>
                <div class="trait"></div>
                <div class="role">L'administration</div>
            </td>
        </tr>
    </table>

    {{-- Le pied de page « Page X / Y » + filet sont dessinés sur chaque page
         par le service PDF (callback canvas DomPDF), dans la marge basse. --}}

</body>

</html>
