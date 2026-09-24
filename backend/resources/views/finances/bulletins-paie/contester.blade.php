@extends('panel.layouts.app')

@section('title', 'Contester le bulletin ' . $bulletin->numero)

@section('content')
<div class="container-fluid">

    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <h1 class="mb-0">Contester le bulletin</h1>
                <p class="text-muted mb-0">
                    Bulletin {{ $bulletin->numero }} —
                    {{ $bulletin->enseignant?->user?->prenom }}
                    {{ $bulletin->enseignant?->user?->nom }}
                </p>
            </div>
        </div>

        <div class="heading-actions">
            <a href="{{ route('finance.bulletins-paie.show', $bulletin) }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Motif de la contestation</h5>
                </div>

                <div class="panel-body">
                    <form method="POST"
                          action="{{ route('finance.bulletins-paie.contester', $bulletin) }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Expliquez le problème
                            </label>
                            <textarea name="commentaire_enseignant"
                                      class="form-control @error('commentaire_enseignant') is-invalid @enderror"
                                      rows="5"
                                      placeholder="Décrivez ce qui ne va pas dans ce bulletin..."
                                      required>{{ old('commentaire_enseignant') }}</textarea>
                            @error('commentaire_enseignant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Confirmer la contestation ?')">
                            <i class="bi bi-exclamation-triangle"></i> Soumettre la contestation
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-header">
                    <h5 class="mb-0">Résumé du bulletin</h5>
                </div>

                <div class="panel-body">
                    <p><strong>Total heures :</strong>
                        {{ number_format($bulletin->total_heures, 2) }} h
                    </p>
                    <p><strong>Montant brut :</strong>
                        {{ number_format($bulletin->montant_brut, 0, ',', ' ') }} FCFA
                    </p>
                    <p><strong>Net à payer :</strong>
                        {{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
