@extends('panel.layouts.app')

@section('title', 'Activation du compte élève')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-person-check"></i>
            </div>

            <div>
                <h1 class="mb-0">
                    Activation du compte élève
                </h1>

                <p class="text-muted mb-0">
                    {{ $eleve->user?->nom }}
                    {{ $eleve->user?->prenom }}
                </p>
            </div>

        </div>

    </div>

    <form
        method="POST"
        action="{{ route('eleves.account.activate', $eleve) }}"
    >
        @csrf

        <div class="panel">

            <div class="panel-header">
                <h5 class="mb-0">
                    Informations de connexion
                </h5>
            </div>

            <div class="p-4">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                        >

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Mot de passe
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                        >

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Confirmation du mot de passe
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-control"
                        >
                    </div>

                </div>

            </div>

        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">

            <a href="{{ route('eleves.show', $eleve) }}"
            class="btn btn-light">

                <i class="bi bi-x-circle"></i>
                Annuler

            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-check-circle"></i>
                Activer le compte
            </button>

        </div>

    </form>

</div>

@endsection