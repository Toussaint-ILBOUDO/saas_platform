@extends('publicpages.layouts.public')

@section('title', 'Demande de cours | K\'Educ')
@section('meta_description', 'Demandez un cours d\'appui à domicile ou en ligne au Burkina Faso : remplissez le formulaire et nous vous accompagnerons.')

@section('content')

<div class="container py-5">

    <div class="text-center mb-5">
        <h1 class="fw-bold">Demande de cours d’appui</h1>
        <p class="text-muted">
            Remplissez le formulaire et nous vous contacterons pour organiser le meilleur accompagnement.
        </p>
    </div>

    <div class="row justify-content-center">

        <div class="col-lg-9">

            <div class="card shadow-sm border-0 rounded-4">

                <div class="card-body p-4 p-lg-5">

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('demande-cours.store') }}">
                        @csrf

                        {{-- STEP 1 --}}
                        <div class="mb-4">
                            <h5 class="mb-3 text-primary">
                                1. Informations du parent
                            </h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Nom</label>
                                    <input class="form-control" name="nom_parent" placeholder="Ex: Diallo">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Prénom</label>
                                    <input class="form-control" name="prenom_parent" placeholder="Ex: Amadou">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input class="form-control" name="telephone" placeholder="+226 70 00 00 00">
                                    <small class="text-muted">Nous vous contacterons sur ce numéro</small>
                                </div>

                            </div>
                        </div>

                        <hr>

                        {{-- STEP 2 --}}
                        <div class="mb-4">
                            <h5 class="mb-3 text-primary">
                                2. Détails du cours
                            </h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Type de cours</label>
                                    <select class="form-select" name="type_cours_id">
                                        <option value="">-- Choisir --</option>
                                        @foreach($typesCours as $type)
                                            <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Classe</label>
                                    <select class="form-select" name="classe_id">
                                        <option value="">-- Choisir --</option>
                                        @foreach($classes as $classe)
                                            <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Volume horaire</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="volume_horaire_estime" placeholder="Ex: 10">
                                        <span class="input-group-text">h/semaine</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <hr>

                        {{-- STEP 3 --}}
                        <div class="mb-4">
                            <h5 class="mb-3 text-primary">
                                3. Matières concernées
                            </h5>

                            <div class="row g-2">

                                @foreach($matieres as $matiere)
                                    <div class="col-md-4">
                                        <label class="form-check border rounded p-2 w-100">
                                            <input class="form-check-input me-2"
                                                   type="checkbox"
                                                   name="matieres[]"
                                                   value="{{ $matiere->id }}">
                                            <span class="form-check-label">
                                                {{ $matiere->nom }}
                                            </span>
                                        </label>
                                    </div>
                                @endforeach

                            </div>
                        </div>

                        <hr>

                        {{-- STEP 4 --}}
                        <div class="mb-4">
                            <h5 class="mb-3 text-primary">
                                4. Message complémentaire
                            </h5>

                            <textarea class="form-control"
                                      name="message"
                                      rows="4"
                                      placeholder="Ex: difficultés en maths, préparation examen..."></textarea>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-success px-5">
                                Envoyer la demande
                            </button>
                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection