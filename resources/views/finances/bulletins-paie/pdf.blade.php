<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie {{ $bulletin->numero }}</title>
    <style>
        /* ==========================================================
           Base — dompdf ne supporte PAS flexbox de façon fiable.
           Tout le layout ci-dessous est reconstruit en tables/blocs
           flottants, seule approche stable pour un rendu PDF A4.
           ========================================================== */
        @page {
            size: A4;
            margin: 2cm;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 100%;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            width: 100%;
        }

        /* EN-TÊTE CABINET — table au lieu de flex, largeurs fixes */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #1a5276;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-table td {
            vertical-align: top;
            padding-bottom: 10px;
        }

        .header-left-cell {
            width: 65%;
        }

        .header-right-cell {
            width: 35%;
            text-align: right;
            font-size: 10px;
            color: #555;
        }

        .header-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        .header-cabinet-inline {
            display: inline;
        }

        .header-cabinet h1 {
            font-size: 16px;
            color: #1a5276;
            margin-bottom: 2px;
        }

        .header-cabinet p {
            font-size: 10px;
            color: #666;
        }

        .header-right-cell p {
            margin-bottom: 2px;
            word-wrap: break-word;
        }

        /* TITRE BULLETIN */
        .bulletin-title {
            text-align: center;
            margin: 15px 0;
        }

        .bulletin-title h2 {
            font-size: 14px;
            color: #1a5276;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 2px solid #1a5276;
            display: inline-block;
            padding: 6px 16px;
        }

        .bulletin-title .numero {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        /* INFORMATIONS — table au lieu de flex */
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 15px;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
        }

        .info-box h3 {
            font-size: 11px;
            color: #1a5276;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .info-box p {
            font-size: 10px;
            margin-bottom: 3px;
        }

        /* TABLEAU PRINCIPAL */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            table-layout: fixed;
        }

        table.data-table thead th {
            background-color: #1a5276;
            color: white;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
        }

        table.data-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
            word-wrap: break-word;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table.data-table tfoot td {
            padding: 7px 8px;
            font-weight: bold;
            border-top: 2px solid #1a5276;
            font-size: 10px;
        }

        .col-eleve   { width: 30%; }
        .col-matiere { width: 22%; }
        .col-heures  { width: 14%; }
        .col-taux    { width: 17%; }
        .col-montant { width: 17%; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* RÉCAPITULATIF */
        .recap {
            margin-top: 12px;
        }

        .recap-table {
            width: 55%;
            margin-left: 45%;
        }

        .recap-table td {
            padding: 5px 8px;
            font-size: 10px;
        }

        .recap-table .total-row td {
            border-top: 2px solid #1a5276;
            font-weight: bold;
            font-size: 12px;
            color: #1a5276;
        }

        /* AJUSTEMENTS */
        .ajustements-section {
            margin-top: 12px;
            margin-bottom: 12px;
        }

        .ajustements-section h3 {
            font-size: 11px;
            color: #1a5276;
            margin-bottom: 6px;
        }

        /* STATUT */
        .statut-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .statut-valide { background: #d4edda; color: #155724; }
        .statut-verse { background: #d1ecf1; color: #0c5460; }
        .statut-genere { background: #cce5ff; color: #004085; }
        .statut-conteste { background: #f8d7da; color: #721c24; }

        /* SIGNATURE — table au lieu de flex */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            font-size: 10px;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 45px;
            padding-top: 5px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        /* FOOTER */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">

        {{-- EN-TÊTE CABINET --}}
        <table class="header-table">
            <tr>
                <td class="header-left-cell">
                    @if(!empty($cabinet['logo']) && file_exists($cabinet['logo']))
                        <img src="{{ $cabinet['logo'] }}" alt="Logo" class="header-logo">
                    @endif
                    <div class="header-cabinet">
                        <h1>{{ $cabinet['nom'] }}</h1>
                        <p>{{ $cabinet['slogan'] }}</p>
                    </div>
                </td>
                <td class="header-right-cell">
                    <p><strong>{{ $cabinet['directeur'] }}</strong></p>
                    <p>{{ $cabinet['adresse'] }}</p>
                    <p>Tél : {{ $cabinet['telephone'] }}</p>
                    <p>WhatsApp : {{ $cabinet['whatsapp'] }}</p>
                    <p>Email : {{ $cabinet['email'] }}</p>
                </td>
            </tr>
        </table>

        {{-- TITRE --}}
        <div class="bulletin-title">
            <h2>Bulletin de Paie</h2>
            <div class="numero">{{ $bulletin->numero }}</div>
        </div>

        {{-- INFORMATIONS --}}
        <table class="info-table">
            <tr>
                <td class="info-box">
                    <h3>Enseignant</h3>
                    <p><strong>Nom :</strong> {{ $bulletin->enseignant?->user?->nom }}</p>
                    <p><strong>Prénom :</strong> {{ $bulletin->enseignant?->user?->prenom }}</p>
                    <p><strong>Téléphone :</strong> {{ $bulletin->enseignant?->user?->telephone_appel ?? '—' }}</p>
                </td>
                <td class="info-box">
                    <h3>Période</h3>
                    <p><strong>Période :</strong> {{ $bulletin->periode?->label }}</p>
                    <p><strong>Du :</strong> {{ $bulletin->periode?->date_debut?->format('d/m/Y') ?? '—' }}</p>
                    <p><strong>Au :</strong> {{ $bulletin->periode?->date_fin?->format('d/m/Y') ?? '—' }}</p>
                    <p><strong>Statut :</strong>
                        @switch($bulletin->statut)
                            @case('valide')
                                <span class="statut-badge statut-valide">Validé</span>
                                @break
                            @case('verse')
                                <span class="statut-badge statut-verse">Versé</span>
                                @break
                            @case('genere')
                                <span class="statut-badge statut-genere">Généré</span>
                                @break
                            @default
                                <span class="statut-badge statut-conteste">{{ ucfirst($bulletin->statut) }}</span>
                        @endswitch
                    </p>
                </td>
            </tr>
        </table>

        {{-- TABLEAU DÉTAIL --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-eleve">Famille / Élève</th>
                    <th class="col-matiere">Matière</th>
                    <th class="col-heures text-center">Heures</th>
                    <th class="col-taux text-right">Taux horaire</th>
                    <th class="col-montant text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bulletin->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->eleve?->user?->nom }} {{ $ligne->eleve?->user?->prenom }}</td>
                        <td>{{ $ligne->matiere?->nom ?? '—' }}</td>
                        <td class="text-center">{{ number_format($ligne->nombre_heures, 2) }} h</td>
                        <td class="text-right">{{ number_format($ligne->taux_horaire, 0, ',', ' ') }} FCFA</td>
                        <td class="text-right">{{ number_format($ligne->montant, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL HEURES</td>
                    <td class="text-center">{{ number_format($bulletin->total_heures, 2) }} h</td>
                    <td></td>
                    <td class="text-right">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA</td>
                </tr>
            </tfoot>
        </table>

        {{-- AJUSTEMENTS --}}
        @if($bulletin->ajustements->isNotEmpty())
            <div class="ajustements-section">
                <h3>Ajustements</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:25%">Type</th>
                            <th style="width:50%">Libellé</th>
                            <th class="text-right" style="width:25%">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulletin->ajustements as $ajustement)
                            <tr>
                                <td>{{ ucfirst($ajustement->type) }}</td>
                                <td>{{ $ajustement->libelle }}</td>
                                <td class="text-right">
                                    @if($ajustement->type === 'prime')
                                        +{{ number_format($ajustement->montant, 0, ',', ' ') }} FCFA
                                    @else
                                        -{{ number_format($ajustement->montant, 0, ',', ' ') }} FCFA
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- RÉCAPITULATIF --}}
        <div class="recap">
            <table class="recap-table">
                <tr>
                    <td>Montant brut :</td>
                    <td class="text-right">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td>Frais de suivi :</td>
                    <td class="text-right">- {{ number_format($bulletin->frais_suivi, 0, ',', ' ') }} FCFA</td>
                </tr>
                @if($total_primes > 0)
                    <tr>
                        <td>Primes :</td>
                        <td class="text-right">+ {{ number_format($total_primes, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endif
                @if($total_retenues > 0)
                    <tr>
                        <td>Retenues :</td>
                        <td class="text-right">- {{ number_format($total_retenues, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>NET À PAYER :</td>
                    <td class="text-right">{{ number_format($bulletin->montant_brut - $bulletin->frais_suivi + $total_primes - $total_retenues, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
        </div>

        {{-- SIGNATURE --}}
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line">Signature de l'enseignant</div>
                </td>
                <td>
                    <div class="signature-line">Signature du cabinet</div>
                </td>
            </tr>
        </table>

        {{-- FOOTER --}}
        <div class="footer">
            <p>{{ $bulletin->numero }} — {{ $bulletin->periode?->label }}</p>
            <p>{{ $cabinet['nom'] }} — {{ $cabinet['adresse'] }} — Tél : {{ $cabinet['telephone'] }}</p>
        </div>

    </div>
</body>
</html>