@extends('pdf.layouts.document')

@section('document_contenu')

    {{-- Informations client --}}
    <div class="section">
        <div class="section-titre">Informations client</div>

        <table class="infos">
            <tr>
                <td class="lbl">Nom</td>
                <td class="val">{{ $commande->nom_client }}</td>

                <td class="lbl">Téléphone</td>
                <td class="val">{{ $commande->telephone_client }}</td>
            </tr>

            <tr>
                <td class="lbl">WhatsApp</td>
                <td class="val">{{ $commande->whatsapp ?? '—' }}</td>

                <td class="lbl">Quartier</td>
                <td class="val">{{ $commande->quartier ?? '—' }}</td>
            </tr>

            <tr>
                <td class="lbl">Adresse</td>
                <td class="val">{{ $commande->adresse_livraison }}</td>

                <td class="lbl">Livraison</td>
                <td class="val">{{ $commande->is_livraison ? 'Oui' : 'Non' }}</td>
            </tr>

            <tr>
                <td class="lbl">Paiement</td>
                <td class="val">
                    {{ $commande->mode_paiement ? ucfirst(str_replace('_', ' ', $commande->mode_paiement)) : '—' }}
                </td>

                <td class="lbl">Compte lié</td>
                <td class="val">
                    @if($commande->user)
                        {{ $commande->user->prenom }} {{ $commande->user->nom }}
                        ({{ $commande->user->email }})
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>
    </div>


    {{-- Articles commandés --}}
    <div class="section">
        <div class="section-titre">Articles commandés</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 50%">Produit</th>
                    <th class="num" style="width: 15%">Quantité</th>
                    <th class="num" style="width: 17%">Prix unitaire</th>
                    <th class="num" style="width: 18%">Sous-total</th>
                </tr>
            </thead>

            <tbody>
                @forelse($lignes as $ligne)
                    <tr class="{{ $loop->index % 2 === 1 ? 'alt' : '' }}">
                        <td>{{ $ligne->produit?->nom ?? 'Produit supprimé' }}</td>
                        <td class="num">{{ $ligne->quantite }}</td>
                        <td class="num">{{ number_format((float) $ligne->prix_unitaire, 0, ',', ' ') }} F</td>
                        <td class="num">
                            <strong>{{ number_format((float) $ligne->sous_total, 0, ',', ' ') }} F</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="color:#94a3b8; font-style:italic;">
                            Aucun article.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>


    {{-- Récapitulatif --}}
    <div class="section">
        <div class="section-titre">Récapitulatif</div>

        <table class="data" style="width: 58%; margin-left: 42%;">
            <tbody>
                <tr>
                    <td>Sous-total</td>
                    <td class="num">{{ number_format((float) $commande->montant_total, 0, ',', ' ') }} F</td>
                </tr>

                @if((float) $commande->frais_livraison > 0)
                    <tr class="alt">
                        <td>Frais de livraison</td>
                        <td class="num">{{ number_format((float) $commande->frais_livraison, 0, ',', ' ') }} F</td>
                    </tr>
                @endif

                <tr class="total">
                    <td>Total</td>
                    <td class="num">{{ number_format((float) $commande->montant_avec_livraison, 0, ',', ' ') }} F</td>
                </tr>
            </tbody>
        </table>
    </div>

@endsection


@section('document_mention')
    @if (! empty($cabinet['adresse'] ?? null))
        Fait à {{ $cabinet['adresse'] }}, le
    @else
        Fait le
    @endif
    {{ ($date_edition ?? now())->format('d/m/Y') }}
@endsection
