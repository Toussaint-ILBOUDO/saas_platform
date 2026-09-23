<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Cabinet — {{ $facture->periode_debut }} au {{ $facture->periode_fin }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #333; padding: 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #0d6efd; padding-bottom: 15px; }
        .header-left h1 { font-size: 18px; color: #0d6efd; }
        .header-left p { color: #666; margin-top: 5px; }
        .header-right { text-align: right; }
        .header-right .label { font-weight: bold; color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; color: #0d6efd; margin-bottom: 10px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .info-item span { color: #666; }
        .info-item strong { display: block; }
        .badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .footer { margin-top: 30px; border-top: 1px solid #dee2e6; padding-top: 15px; text-align: center; color: #666; font-size: 11px; }
        @media print { body { padding: 15px; } }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>{{ $cabinet['nom'] }}</h1>
            <p>{{ $cabinet['adresse'] }}</p>
            <p>Tél : {{ $cabinet['telephone'] }} | WhatsApp : {{ $cabinet['whatsapp'] }}</p>
            <p>Email : {{ $cabinet['email'] }}</p>
        </div>
        <div class="header-right">
            <h2 style="color: #0d6efd;">FACTURE CABINET</h2>
            <p><span class="label">Date :</span> {{ \Carbon\Carbon::parse($facture->date_facture)->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Période de facturation</div>
        <div class="info-grid">
            <div class="info-item">
                <span>Date début</span>
                <strong>{{ \Carbon\Carbon::parse($facture->periode_debut)->format('d/m/Y') }}</strong>
            </div>
            <div class="info-item">
                <span>Date fin</span>
                <strong>{{ \Carbon\Carbon::parse($facture->periode_fin)->format('d/m/Y') }}</strong>
            </div>
            <div class="info-item">
                <span>Statut</span>
                <strong>
                    @if($facture->estPayee())
                        <span class="badge badge-success">Payée</span>
                    @elseif($facture->estPartiellementPayee())
                        <span class="badge badge-warning">Partiellement payée</span>
                    @else
                        <span class="badge badge-danger">En attente</span>
                    @endif
                </strong>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détail des commissions</div>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="text-end">Quantité</th>
                    <th class="text-end">Base calcul</th>
                    <th class="text-end">Taux</th>
                    <th class="text-end">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facture->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->typeCommission?->nom_du_type ?? '—' }}</td>
                        <td class="text-end">{{ number_format($ligne->quantite, 0, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($ligne->base_calcul, 0, ',', ' ') }} F</td>
                        <td class="text-end">{{ number_format($ligne->taux_commission, 1) }}%</td>
                        <td class="text-end"><strong>{{ number_format($ligne->montant, 0, ',', ' ') }} F</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4"><strong>TOTAL DÛ</strong></td>
                    <td class="text-end"><strong>{{ number_format($facture->montant_total_du, 0, ',', ' ') }} F</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4"><strong>MONTANT PAYÉ</strong></td>
                    <td class="text-end"><strong class="text-success">{{ number_format($facture->montant_paye, 0, ',', ' ') }} F</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4"><strong>RESTANT</strong></td>
                    <td class="text-end"><strong class="text-danger">{{ number_format($facture->montant_restant, 0, ',', ' ') }} F</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($facture->paiements->count() > 0)
    <div class="section">
        <div class="section-title">Historique des paiements</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Mode</th>
                    <th>Référence</th>
                    <th class="text-end">Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facture->paiements as $paiement)
                    <tr>
                        <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement)) }}</td>
                        <td>{{ $paiement->reference_paiement ?? '—' }}</td>
                        <td class="text-end">{{ number_format($paiement->montant_paye, 0, ',', ' ') }} F</td>
                        <td>{{ ucfirst($paiement->statut) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>{{ $cabinet['nom'] }} — {{ $cabinet['slogan'] }}</p>
        <p>Directeur : {{ $cabinet['directeur'] }}</p>
    </div>

</body>
</html>
