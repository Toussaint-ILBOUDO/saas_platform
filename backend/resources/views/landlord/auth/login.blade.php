<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | Plateforme SaasCD</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/landlord-login.css') }}">
</head>

<body class="login-page">

    <section class="login-page-wrapper">
        <div class="container-fluid">
            <div class="row g-0 min-vh-100">

                <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center p-4 bg-dark text-white">
                    <div class="text-center mb-4">
                        <h1 class="display-6 fw-bold">SaasCD</h1>
                        <p class="lead mb-0">Plateforme de gestion des cabinets K'Educ</p>
                    </div>
                    <p class="text-center text-white-50 small mb-0">
                        Centralisez la facturation, les cabinets et le journal de la plateforme.
                    </p>
                </div>

                <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center p-4">
                    <div class="w-100" style="max-width: 420px;">
                        <h2 class="h4 fw-bold mb-1">Espace super-admin</h2>
                        <p class="text-secondary mb-4">Connectez-vous pour administrer la plateforme.</p>

                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <ul class="mb-0 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('landlord.login.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       required autofocus autocomplete="username">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input id="password" type="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required autocomplete="current-password">
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                                Se connecter
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

</body>
</html>