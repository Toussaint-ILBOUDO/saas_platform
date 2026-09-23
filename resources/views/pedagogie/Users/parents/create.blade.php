@extends('panel.layouts.app')

@section('title', 'Nouveau parent')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-people"></i>
            </div>

            <div>
                <h1 class="mb-0">Nouveau parent</h1>
                <p class="text-muted mb-0">
                    Création d'un compte parent
                </p>
            </div>

        </div>

    </div>

    <form action="{{ route('parents.store') }}" method="POST">

        @csrf

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Informations générales
                </h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Nom
                        </label>

                        <input type="text"
                               name="nom"
                               value="{{ old('nom') }}"
                               class="form-control @error('nom') is-invalid @enderror">

                        @error('nom')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Prénom
                        </label>

                        <input type="text"
                               name="prenom"
                               value="{{ old('prenom') }}"
                               class="form-control @error('prenom') is-invalid @enderror">

                        @error('prenom')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Téléphone WhatsApp
                        </label>

                        <input type="text"
                               name="telephone_whatsapp"
                               value="{{ old('telephone_whatsapp') }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Téléphone appel
                        </label>

                        <input type="text"
                               name="telephone_appel"
                               value="{{ old('telephone_appel') }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Mot de passe
                        </label>

                        <input type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror">

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>

        </div>

        <div class="panel mb-4">

            <div class="panel-header">
                <h5 class="mb-0">
                    Profil parent
                </h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Profession
                        </label>

                        <input type="text"
                               name="profession"
                               value="{{ old('profession') }}"
                               class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Nombre d'enfants
                        </label>

                        <input type="number"
                               min="0"
                               name="nombre_enfants"
                               value="{{ old('nombre_enfants') }}"
                               class="form-control">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Adresse
                        </label>

                        <textarea name="adresse_domicile"
                                  rows="3"
                                  class="form-control">{{ old('adresse_domicile') }}</textarea>

                    </div>

                </div>

            </div>

        </div>

        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('parents.index') }}"
            class="btn btn-light">

                <i class="bi bi-x-circle"></i>
                Annuler

            </a>

            <button type="submit"
                    class="btn btn-primary">

                <i class="bi bi-check-circle"></i>
                Enregistrer

            </button>

        </div>

    </form>

</div>

@endsection