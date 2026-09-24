<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | K'Educ</title>
    <meta name="description" content="Connectez-vous à votre espace K'Educ : élèves, parents, enseignants et administrateurs.">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Vendor -->
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/main.css') }}">
</head>

<body class="login-page">

<section class="login-page-wrapper">

    <div class="container-fluid">

        <div class="row g-0 min-vh-100">

            <!-- LEFT IMAGE -->
            <div class="col-lg-7 d-none d-lg-flex login-left">
                <div class="login-image-wrapper">
                    <img src="{{ asset('templates/publicpages/assets/img/login.png') }}"
                         alt="Illustration de connexion K'Educ"
                         class="login-image">
                </div>
            </div>

            <!-- RIGHT FORM -->
            <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center login-right">

                <div class="login-box">

                    <!-- LOGO -->
                    <div class="text-center mb-4">
                        <img src="{{ asset('assets/img/logo.png') }}"
                             alt="Logo K'Educ"
                             class="login-logo">
                    </div>

                    <h1 class="visually-hidden">Connexion à K'Educ</h1>

                    <h2 class="login-title text-center">Hello, Welcome Back</h2>
                    <p class="text-center text-muted mb-4">Connectez-vous à votre compte</p>

                    @if($errors->any())
                        <div class="alert alert-danger py-2" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf

                        <!-- EMAIL -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   placeholder="Email ou nom d'utilisateur"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- PASSWORD -->
                        <div class="mb-2">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                                   placeholder="Mot de passe"
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- OPTIONS -->
                        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                                <label class="form-check-label" for="remember">
                                    Se souvenir de moi
                                </label>
                            </div>

                            <span class="small text-muted">Mot de passe oublié ?</span>
                        </div>

                        <!-- BUTTON -->
                        <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill">
                            Connexion
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- JS -->
<script src="{{ asset('templates/publicpages/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('templates/publicpages/assets/js/main.js') }}"></script>

</body>
</html>