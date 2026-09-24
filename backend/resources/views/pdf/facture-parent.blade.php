<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture }}</title>

    <style>
        @page {
            margin: 30px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #212529;
        }


        /* HEADER */
        .header {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
        }

        .slogan {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .facture-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            color: #0d6efd;
        }

        .facture-numero {
            font-size: 13px;
            margin-top: 5px;
        }


        /* INFOS */
        .section-title {
            padding: 6px 10px;
            background: #f1f3f5;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        .info-table .label {
            width: 35%;
            background: #f8f9fa;
            font-weight: bold;
        }


        /* LIGNES */
        .lignes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .lignes-table th {
            padding: 6px 8px;
            background: #0d6efd;
            color: white;
            font-size: 10px;
            text-align: left;
            border: 1px solid #0d6efd;
        }

        .lignes-table th.num {
            text-align: right;
        }

        .lignes-table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        .lignes-table td.num {
            text-align: right;
        }

        .lignes-table tr:nth-child(even) {
            background: #f8f9fa;
        }


        /* TOTAUX */
        .totaux-table {
            width: 50%;
            margin-left: 50%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .totaux-table td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
        }

        .totaux-table .label {
            background: #f8f9fa;
            font-weight: bold;
        }

        .totaux-table .total-row td {
            background: #0d6efd;
            color: white;
            font-weight: bold;
            font-size: 13px;
        }


        /* SIGNATURE */
        .signature {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }

        .signature td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 15px;
        }

        .line {
            margin-top: 40px;
            border-top: 1px solid #333;
        }


        /* FOOTER */
        .footer {
            position: fixed;
            bottom: -15px;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #777;
        }

        .cabinet-info {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>


<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="logo">{{ $cabinet['nom'] }}</div>
                <div class="slogan">{{ $cabinet['slogan'] }}</div>
                <div class="cabinet-info">
                    {{ $cabinet['adresse'] }}<br>
                    Tél : {{ $cabinet['telephone'] }}
                    | WhatsApp : {{ $cabinet['whatsapp'] }}<br>
                    Email : {{ $cabinet['email'] }}
                </div>
            </td>
            <td class="header-right">
                <div class="facture-title">Facture</div>
                <div class="facture-numero">
                    <strong>{{ $facture->numero_facture }}</strong>
                </div>
                <div style="margin-top: 5px; font-size: 10px;">
                    Émise le {{ $facture->created_at->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>


    {{-- INFOS FACTURE --}}
    <div class="section-title">Informations de la facture</div>

    <table class="info-table">
        <tr>
            <td class="label">Élève</td>
            <td>
                {{ $eleve?->user?->prenom }} {{ $eleve?->user?->nom }}
            </td>
            <td class="label">Parent</td>
            <td>
                {{ $parent?->prenom }} {{ $parent?->nom }}
            </td>
        </tr>
        <tr>
            <td class="label">Classe</td>
            <td>{{ $eleve?->classe?->sigle ?? '—' }}</td>
            <td class="label">Type de cours</td>
            <td>{{ $facture->contrat?->typeCours?->libelle ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Période</td>
            <td>{{ $periode?->label ?? '—' }}</td>
            <td class="label">Date limite</td>
            <td>
                {{ $facture->date_limite_paiement?->format('d/m/Y') ?? 'Non définie' }}
            </td>
        </tr>
    </table>


    {{-- DÉTAIL DES COURS --}}
    <div class="section-title">Détail des cours</div>

    <table class="lignes-table">
        <thead>
            <tr>
                <th style="width: 30%">Enseignant</th>
                <th style="width: 20%">Matière</th>
                <th class="num" style="width: 15%">Heures</th>
                <th class="num" style="width: 15%">Taux/h</th>
                <th class="num" style="width: 20%">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>
                        {{ $ligne->affectation?->enseignant?->user?->prenom }}
                        {{ $ligne->affectation?->enseignant?->user?->nom }}
                    </td>
                    <td>
                        {{ $ligne->affectation?->matiere?->nom ?? '—' }}
                    </td>
                    <td class="num">
                        {{ number_format($ligne->nombre_heures, 1) }} h
                    </td>
                    <td class="num">
                        {{ number_format($ligne->taux_horaire, 0, ',', ' ') }} F
                    </td>
                    <td class="num">
                        <strong>
                            {{ number_format($ligne->montant, 0, ',', ' ') }} F
                        </strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    {{-- RÉCAPITULATIF --}}
    <div class="section-title">Récapitulatif</div>

    <table class="totaux-table">
        <tr>
            <td class="label">Cours ({{ number_format($facture->volume_horaire_total, 1) }} h)</td>
            <td class="num">
                {{ number_format($montantCours, 0, ',', ' ') }} F
            </td>
        </tr>

        @if($facture->frais_suivi > 0)
            <tr>
                <td class="label">Frais de suivi</td>
                <td class="num">
                    {{ number_format($facture->frais_suivi, 0, ',', ' ') }} F
                </td>
            </tr>
        @endif

        @if($facture->autres_frais > 0)
            <tr>
                <td class="label">Autres frais</td>
                <td class="num">
                    {{ number_format($facture->autres_frais, 0, ',', ' ') }} F
                </td>
            </tr>
        @endif

        @if($facture->remise > 0)
            <tr>
                <td class="label">Remise</td>
                <td class="num" style="color: #dc3545;">
                    - {{ number_format($facture->remise, 0, ',', ' ') }} F
                </td>
            </tr>
        @endif

        <tr class="total-row">
            <td class="label">Total à payer</td>
            <td class="num">
                {{ number_format($facture->montant_total, 0, ',', ' ') }} F
            </td>
        </tr>
    </table>


    {{-- PAIEMENT --}}
    @if($facture->estPayee())
        <div class="section-title" style="background: #d1e7dd; color: #0f5132;">
            Paiement reçu
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Statut</td>
                <td>Payée</td>
                <td class="label">Date</td>
                <td>{{ $facture->date_paiement?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Mode</td>
                <td>
                    {{ ucfirst(str_replace('_', ' ', $facture->mode_paiement ?? '—')) }}
                </td>
                <td class="label">Référence</td>
                <td>{{ $facture->reference_paiement ?? '—' }}</td>
            </tr>
        </table>
    @endif


    {{-- SIGNATURES --}}
    <table class="signature">
        <tr>
            <td>
                Le parent
                <div class="line"></div>
            </td>
            <td>
                L'administration
                <div class="line"></div>
            </td>
        </tr>
    </table>


    {{-- FOOTER --}}
    <div class="footer">
        {{ $cabinet['nom'] }} — {{ $cabinet['adresse'] }}
        | Directeur : {{ $cabinet['directeur'] }}
        | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>


</body>
</html>
