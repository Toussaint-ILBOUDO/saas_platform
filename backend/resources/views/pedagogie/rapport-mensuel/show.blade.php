@extends('panel.layouts.app')

@section('title', 'Rapport mensuel')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="page-heading">
        <div class="page-heading-copy">
            <div class="page-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>

            <div>
                <h1 class="mb-0">Rapport mensuel</h1>
                <p class="text-muted mb-0">Synthèse pédagogique de la période</p>
            </div>
        </div>

        <div class="heading-actions d-flex gap-2">
            <a href="{{ route('rapports-mensuels.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Retour
            </a>

            @can('update', $rapport)
                <a href="{{ route('rapports-mensuels.edit', $rapport) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            @endcan
        </div>
    </div>


    @php
        $contrat = $rapport->contratCours;
        $eleve = $contrat->eleve->user;
        $enseignant = $rapport->enseignant->user;
        $affectation = $contrat->affectations->firstWhere('enseignant_id', $rapport->enseignant_id);
        $matiereNom = $affectation?->matiere?->nom ?? '—';
    @endphp


    {{-- INFORMATIONS PRINCIPALES --}}
    <div class="row g-3 mb-4">

        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-primary">
                <div class="metric-label">Élève</div>
                <div class="metric-value">
                    {{ $eleve->prenom }} {{ $eleve->nom }}
                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-success">
                <div class="metric-label">Matière</div>
                <div class="metric-value">
                    {{ $matiereNom }}
                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="metric-card metric-warning">
                <div class="metric-label">Enseignant</div>
                <div class="metric-value">
                    {{ $enseignant->prenom }} {{ $enseignant->nom }}
                </div>
            </div>
        </div>


        <div class="col-lg-3 col-md-6">
            <div class="metric-card">
                <div class="metric-label">Volume horaire</div>
                <div class="metric-value">
                    {{ number_format($rapport->volume_horaire_cumule, 1) }} h
                </div>
            </div>
        </div>

    </div>



    {{-- PERIODE --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Période comptable</h5>
        </div>

        <div class="panel-body">
            <ul class="info-list">

                <li>
                    <span>Période</span>
                    <strong>{{ $rapport->periode->label ?? 'Période' }}</strong>
                </li>

                <li>
                    <span>Date début</span>
                    <strong>{{ $rapport->periode->date_debut->format('d/m/Y') }}</strong>
                </li>

                <li>
                    <span>Date fin</span>
                    <strong>{{ $rapport->periode->date_fin->format('d/m/Y') }}</strong>
                </li>

                <li>
                    <span>Statut</span>
                    <strong>
                        @php
                            $couleurStatut = match ($rapport->statut) {
                                'valide' => 'text-bg-success',
                                'rejete' => 'text-bg-danger',
                                'soumis' => 'text-bg-warning',
                                default => 'text-bg-secondary',
                            };
                        @endphp

                        <span class="badge {{ $couleurStatut }}">
                            {{ ucfirst($rapport->statut) }}
                        </span>
                    </strong>
                </li>


                @if($rapport->date_validation)
                    <li>
                        <span>Validé le</span>
                        <strong>{{ $rapport->date_validation->format('d/m/Y H:i') }}</strong>
                    </li>
                @endif


                @if($rapport->motif_rejet)
                    <li>
                        <span>Motif du rejet</span>
                        <strong class="text-danger">{{ $rapport->motif_rejet }}</strong>
                    </li>
                @endif

            </ul>
        </div>

    </div>



    {{-- BILAN AUTOMATIQUE --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Bilan des activités</h5>
        </div>

        <div class="panel-body">
            {!! nl2br(e($rapport->bilan_activites)) !!}
        </div>

    </div>



    {{-- SUIVI PEDAGOGIQUE --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Suivi pédagogique</h5>
        </div>

        <div class="panel-body">

            <div class="row g-4">

                <div class="col-md-6">
                    <h6>Points notables matière</h6>
                    <p class="text-muted">
                        {{ $rapport->point_notes_matieres ?: 'Aucune information.' }}
                    </p>
                </div>

                <div class="col-md-6">
                    <h6>Autres matières</h6>
                    <p class="text-muted">
                        {{ $rapport->point_notes_autres_matieres ?: 'Aucune information.' }}
                    </p>
                </div>

            </div>

        </div>

    </div>



    {{-- ANALYSE --}}
    <div class="panel mb-4">

        <div class="panel-header">
            <h5 class="mb-0">Analyse et observations</h5>
        </div>

        <div class="panel-body">

            <div class="row g-4">

                @foreach([
                    'Difficultés rencontrées' => $rapport->difficultes_rencontrees ?: 'Aucune difficulté signalée.',
                    'Solutions trouvées' => $rapport->solutions_trouvees ?: 'Aucune solution renseignée.',
                    'Attentes parents / élève' => $rapport->attentes_parents_eleve ?: 'Aucune attente.',
                    'Attentes administration' => $rapport->attentes_administration ?: 'Aucune attente.'
                ] as $titre => $contenu)

                    <div class="col-md-6">
                        <strong>{{ $titre }}</strong>
                        <p class="text-muted mt-2">{{ $contenu }}</p>
                    </div>

                @endforeach


                <div class="col-12">
                    <strong>Appréciation évolution</strong>
                    <p class="text-muted mt-2">
                        {{ $rapport->appreciation_evolution ?: 'Aucune appréciation.' }}
                    </p>
                </div>


                <div class="col-12">
                    <strong>Observations générales</strong>
                    <p class="text-muted mt-2">
                        {{ $rapport->observations ?: 'Aucune observation.' }}
                    </p>
                </div>

            </div>

        </div>

    </div>



    {{-- VALIDATION / REJET PAR L'ADMINISTRATION (D-051) --}}
    @role('admin_cabinet')

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Décision de l'administration</h5>
            </div>

            <div class="panel-body">

                @if($rapport->statut === 'soumis')

                    <p class="text-muted">
                        La validation débloque la facturation au parent et la
                        paie de l'enseignant. Un rejet doit être motivé :
                        l'enseignant est notifié du motif.
                    </p>

                    <div class="d-flex gap-2 mb-4">

                        <form method="POST"
                              action="{{ route('rapports-mensuels.validate', $rapport) }}"
                              onsubmit="return confirm('Valider ce rapport ?');">

                            @csrf

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i>
                                Valider le rapport
                            </button>

                        </form>

                    </div>

                    <form method="POST"
                          action="{{ route('rapports-mensuels.reject', $rapport) }}">

                        @csrf

                        <div class="mb-2">
                            <label class="form-label" for="motif_rejet">
                                Motif du rejet <span class="text-danger">*</span>
                            </label>

                            <textarea name="motif_rejet"
                                      id="motif_rejet"
                                      rows="3"
                                      class="form-control @error('motif_rejet') is-invalid @enderror"
                                      required
                                      minlength="5"
                                      placeholder="Ce que l'enseignant doit corriger.">{{ old('motif_rejet') }}</textarea>

                            @error('motif_rejet')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-x-circle"></i>
                            Rejeter le rapport
                        </button>

                    </form>

                @elseif($rapport->statut === 'valide')

                    <p class="text-muted mb-0">
                        Rapport validé{{ $rapport->date_validation ? ' le ' . $rapport->date_validation->format('d/m/Y') : '' }} :
                        il n'est plus modifiable ni supprimable, et il alimente la
                        facture comme le bulletin de paie.
                    </p>

                @else

                    <p class="text-muted mb-0">
                        Rapport {{ $rapport->statut === 'rejete' ? 'rejeté' : 'en brouillon' }} :
                        seule une validation peut débloquer la facturation.
                    </p>

                @endif

            </div>

        </div>

    @endrole

</div>
@endsection