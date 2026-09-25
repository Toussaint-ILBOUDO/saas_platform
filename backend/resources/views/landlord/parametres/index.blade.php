@extends('landlord.layouts.app')

@section('title', 'Paramètres de la plateforme')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Paramètres de la plateforme</h1>
    </div>

    <form method="POST" action="{{ route('landlord.parametres.update') }}" class="card shadow-sm">
        @csrf
        @method('PUT')

        <div class="card-header bg-white fw-semibold">Interrupteur d'accès aux écrans web KEduc</div>
        <div class="card-body">
            <p class="text-secondary mb-3">
                Par décision B4, les vues et routes web de la copie KEduc sont gelées et
                <strong>ne sont pas servies aux cabinets par défaut</strong> (référence fonctionnelle
                uniquement). Activez cet interrupteur pour les redélivrer sur les domaines cabinets.
                L'API et l'impersonation ne sont jamais affectées.
            </p>

            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="acces-ecrans-web-keduc" name="acces_ecrans_web_keduc" value="1"
                       @checked($accesWebKeduc)>
                <label class="form-check-label" for="acces-ecrans-web-keduc">
                    Servir les écrans web KEduc aux cabinets
                </label>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
@endsection