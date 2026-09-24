<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Commande {{ $commande->reference }}</title>

    <style>
        @page {
            margin: 30px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #212529;
        }

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

        .doc-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            color: #0d6efd;
        }

        .doc-numero {
            font-size: 13px;
            margin-top: 5px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 8px;
        }

        .status-en_attente { background: #ffc107; color: #212529; }
        .status-confirmee { background: #0dcaf0; color: #212529; }
        .status-en_preparation { background: #0d6efd; color: #fff; }
        .status-livree { background: #198754; color: #fff; }
        .status-annulee { background: #dc3545; color: #fff; }

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
                <div class="doc-title">Commande</div>
                <div class="doc-numero">
                    <strong>{{ $commande->reference }}</strong>
                </div>
                <div style="margin-top: 5px; font-size: 10px;">
                    Passée le {{ $commande->created_at->format('d/m/Y à H:i') }}
                </div>
                <div class="status-badge status-{{ $commande->statut }}">
                    {{ \App\Models\Commande::statutLabel($commande->statut) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- INFOS CLIENT --}}
    <div class="section-title">Informations client</div>

    <table class="info-table">
        <tr>
            <td class="label">Nom</td>
            <td>{{ $commande->nom_client }}</td>
            <td class="label">Téléphone</td>
            <td>{{ $commande->telephone_client }}</td>
            <td class="label">WhatsApp</td>
            <td>{{ $commande->whatsapp ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Adresse</td>
            <td>{{ $commande->adresse_livraison }}</td>
            <td class="label">Quartier</td>
            <td>{{ $commande->quartier ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Livraison</td>
            <td>{{ $commande->is_livraison ? 'Oui' : 'Non' }}</td>
            <td class="label">Paiement</td>
            <td>{{ $commande->mode_paiement ? ucfirst(str_replace('_', ' ', $commande->mode_paiement)) : '—' }}</td>
        </tr>
        @if($commande->user)
        <tr>
            <td class="label">Compte lié</td>
            <td colspan="3">{{ $commande->user->prenom }} {{ $commande->user->nom }} ({{ $commande->user->email }})</td>
        </tr>
        @endif
    </table>

    {{-- ARTICLES --}}
    <div class="section-title">Articles commandés</div>

    <table class="lignes-table">
        <thead>
            <tr>
                <th style="width: 50%">Produit</th>
                <th class="num" style="width: 15%">Quantité</th>
                <th class="num" style="width: 15%">Prix unitaire</th>
                <th class="num" style="width: 20%">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne->produit?->nom ?? 'Produit supprimé' }}</td>
                    <td class="num">{{ $ligne->quantite }}</td>
                    <td class="num">{{ number_format((float) $ligne->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="num"><strong>{{ number_format((float) $ligne->sous_total, 0, ',', ' ') }} F</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- RÉCAPITULATIF --}}
    <div class="section-title">Récapitulatif</div>

    <table class="totaux-table">
        <tr>
            <td class="label">Sous-total</td>
            <td class="num">{{ number_format((float) $commande->montant_total, 0, ',', ' ') }} F</td>
        </tr>

        @if((float) $commande->frais_livraison > 0)
            <tr>
                <td class="label">Frais de livraison</td>
                <td class="num">{{ number_format((float) $commande->frais_livraison, 0, ',', ' ') }} F</td>
            </tr>
        @endif

        <tr class="total-row">
            <td class="label">Total</td>
            <td class="num">{{ number_format((float) $commande->montant_avec_livraison, 0, ',', ' ') }} F</td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <div class="footer">
        {{ $cabinet['nom'] }} — {{ $cabinet['adresse'] }}
        | Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>

</body>
</html>
