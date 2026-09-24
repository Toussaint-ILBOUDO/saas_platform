@extends('panel.layouts.app')

@section('title', 'Nouvel élève')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-mortarboard"></i>
            </div>

            <div>
                <h1 class="mb-0">Nouvel élève</h1>
                <p class="text-muted mb-0">
                    Création d'un profil élève (compte désactivé par défaut)
                </p>
            </div>

        </div>

    </div>

    <form action="{{ route('eleves.store') }}" method="POST">

        @csrf

        {{-- =========================
            IDENTITÉ ÉLÈVE
        ========================== --}}
        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Informations de l'élève</h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom" value="{{ old('nom') }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Prénom</label>
                        <input type="text" name="prenom" value="{{ old('prenom') }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Parent responsable</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">Rechercher un parent...</option>

                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}">
                                    {{ $parent->nom }} {{ $parent->prenom }}
                                    - {{ $parent->telephone_whatsapp }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Classe</label>
                        <select name="classe_id" class="form-select">
                            <option value="">Sélectionner</option>

                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">
                                    {{ $classe->nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Lieu de naissance</label>
                        <input type="text" name="lieu_naissance" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">École</label>
                        <input type="text" name="ecole" class="form-control">
                    </div>

                </div>

            </div>
        </div>

        {{-- =========================
            INFOS PARENTS / PROFIL
        ========================== --}}
        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Informations complémentaires</h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">
                        <input class="form-control" name="profession_pere" placeholder="Profession du père">
                    </div>

                    <div class="col-md-6">
                        <input class="form-control" name="profession_mere" placeholder="Profession de la mère">
                    </div>

                    <div class="col-md-6">
                        <input class="form-control" name="regime_etude" placeholder="Régime d'étude (interne/externe)">
                    </div>

                    <div class="col-md-6">
                        <input class="form-control" name="religion_enfant" placeholder="Religion">
                    </div>

                    <div class="col-12">
                        <textarea class="form-control" rows="3"
                                  name="maladies_allergies"
                                  placeholder="Maladies ou allergies"></textarea>
                    </div>

                    <div class="col-12">
                        <textarea class="form-control" rows="3"
                                  name="autres_observations"
                                  placeholder="Observations générales"></textarea>
                    </div>

                </div>

            </div>
        </div>

        {{-- =========================
            INFO SYSTÈME (IMPORTANT)
        ========================== --}}
        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">Compte utilisateur</h5>
            </div>

            <div class="p-4">

                <div class="alert alert-info mb-0">

                    ✔ Un compte utilisateur sera automatiquement créé<br>
                    ✔ Le compte sera <strong>inactif</strong> par défaut<br>
                    ✔ L’activation se fera depuis la fiche élève

                </div>

            </div>

        </div>

        {{-- =========================
            SUBMIT
        ========================== --}}
        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('eleves.index') }}"
            class="btn btn-light">

                <i class="bi bi-x-circle"></i>
                Annuler

            </a>

            <button type="submit"
                    class="btn btn-primary">

                <i class="bi bi-check-circle"></i>
                Enregistrer l'élève

            </button>

        </div>

    </form>

</div>

@endsection

@push('scripts')
<script>
    new TomSelect('#parent_id', {
        create: false,
        maxOptions: 500,
        placeholder: 'Tapez le nom du parent...',
        searchField: ['text']
    });
</script>
@endpush