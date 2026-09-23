@extends('panel.layouts.app')

@section('title')
    Commande {{ $commande->reference }} — Librairie
@endsection

@section('content')

<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-receipt"></i></div>
            <div>
                <h1 class="mb-0">Commande {{ $commande->reference }}</h1>
                <p class="text-muted mb-0">Passée le {{ $commande->created_at->format('d/m/Y à H:i') }}</p>
            </div>
        </div>
        <div class="heading-actions d-flex gap-2">
            <a href="{{ route('admin.librairie.commandes.pdf', $commande) }}" class="btn btn-sm btn-success" target="_blank">
                <i class="bi bi-file-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('admin.librairie.commandes.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-box-seam me-2"></i>Articles commandés
                        <span class="badge bg-secondary ms-2">{{ $commande->lignes->sum('quantite') }} article(s)</span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Prix unitaire</th>
                                    <th class="text-end">Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($commande->lignes as $ligne)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="{{ $ligne->produit?->image_url }}" alt="{{ $ligne->produit?->nom ?? 'Produit' }}" class="lib-thumb-sm" loading="lazy">
                                                <span class="fw-semibold">{{ $ligne->produit?->nom ?? 'Produit supprimé' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $ligne->quantite }}</td>
                                        <td class="text-end">{{ number_format((float) $ligne->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                                        <td class="text-end fw-bold">{{ number_format((float) $ligne->sous_total, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Sous-total :</td>
                                    <td class="text-end fw-bold">{{ number_format((float) $commande->montant_total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                @if((float) $commande->frais_livraison > 0)
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">
                                            <i class="bi bi-truck me-1"></i>Livraison :
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format((float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endif
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end fw-bold fs-5">Total :</td>
                                    <td class="text-end fw-bold fs-5">{{ number_format((float) $commande->montant_total + (float) $commande->frais_livraison, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            @if(!empty($commande->statut_modifications))
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-clock-history me-2"></i>Historique des statuts
                        </h6>
                        <div class="timeline-vertical">
                            @foreach(array_reverse($commande->statut_modifications) as $modif)
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="text-center" style="min-width: 44px;">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                                             style="width: 32px; height: 32px; background: var(--accent-color, #e9ecef);">
                                            <i class="bi bi-arrow-repeat small"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div>
                                            <span class="badge {{ \App\Models\Commande::statutBadgeClass($modif['ancien_statut']) }} me-1">
                                                {{ \App\Models\Commande::statutLabel($modif['ancien_statut']) }}
                                            </span>
                                            <i class="bi bi-arrow-right mx-1 text-muted small"></i>
                                            <span class="badge {{ \App\Models\Commande::statutBadgeClass($modif['nouveau_statut']) }} ms-1">
                                                {{ \App\Models\Commande::statutLabel($modif['nouveau_statut']) }}
                                            </span>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            {{ \Carbon\Carbon::parse($modif['created_at'])->format('d/m/Y H:i') }}
                                            @if(!empty($modif['modifie_par']))
                                                &middot; par #{{ $modif['modifie_par'] }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-tag me-2"></i>Statut</h6>
                    <div class="mb-3">
                        <span class="badge {{ \App\Models\Commande::statutBadgeClass($commande->statut) }} fs-6">
                            {{ \App\Models\Commande::statutLabel($commande->statut) }}
                        </span>
                    </div>

                    <form action="{{ route('admin.librairie.commandes.statut', $commande) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <select name="statut" class="form-select form-select-sm">
                                @if($commande->estEnAttente())
                                    <option value="confirmee">Confirmer</option>
                                    <option value="annulee">Annuler</option>
                                @elseif($commande->estConfirmee())
                                    <option value="en_preparation">Mettre en préparation</option>
                                    <option value="annulee">Annuler</option>
                                @elseif($commande->estEnPreparation())
                                    <option value="livree">Marquer livrée</option>
                                @endif
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-check-lg me-1"></i>Mettre à jour le statut
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Informations client</h6>
                    <div class="mb-2">
                        <strong>Nom :</strong>
                        <span>{{ $commande->nom_client }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Téléphone :</strong>
                        <a href="tel:{{ $commande->telephone_client }}" class="text-decoration-none">
                            <i class="bi bi-telephone me-1"></i>{{ $commande->telephone_client }}
                        </a>
                    </div>
                    @if($commande->whatsapp)
                    <div class="mb-2">
                        <strong>WhatsApp :</strong>
                        <a href="{{ $whatsappLink }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                            <i class="bi bi-whatsapp me-1"></i>Contacter sur WhatsApp
                        </a>
                        <div class="text-muted small mt-1">{{ $commande->whatsapp }}</div>
                    </div>
                    @endif
                    <div class="mb-2">
                        <strong>Adresse :</strong>
                        <span>{{ $commande->adresse_livraison }}</span>
                    </div>
                    @if($commande->quartier)
                        <div class="mb-2">
                            <strong>Quartier :</strong>
                            <span>{{ $commande->quartier }}</span>
                        </div>
                    @endif
                    <div class="mb-2">
                        <strong>Livraison :</strong>
                        @if($commande->is_livraison)
                            <span class="badge bg-success-subtle text-success">Oui</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Non</span>
                        @endif
                    </div>
                    @if($commande->mode_paiement)
                        <div class="mb-0">
                            <strong>Paiement :</strong>
                            <span>{{ ucfirst(str_replace('_', ' ', $commande->mode_paiement)) }}</span>
                        </div>
                    @endif
                    @if($commande->user)
                        <hr>
                        <div class="mb-0">
                            <strong>Compte :</strong>
                            <span>{{ $commande->user->prenom }} {{ $commande->user->nom }}</span>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                <i class="bi bi-envelope"></i> {{ $commande->user->email }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($commande->notes)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-chat-dots me-2"></i>Notes</h6>
                        <p class="text-muted mb-0">{{ $commande->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
