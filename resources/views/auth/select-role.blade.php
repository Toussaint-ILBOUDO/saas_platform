<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir un espace</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Vendor -->
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/main.css') }}">
</head>

<body>

<section class="section">
    <div class="container">

        <div class="row justify-content-center align-items-center min-vh-100">

            <div class="col-12 col-md-8 col-lg-5">

                <div class="card shadow-sm border-0 p-4 text-center">

                    <!-- TITLE -->
                    <h2 class="mb-2" style="font-family: var(--heading-font); color: var(--heading-color);">
                        Choisir un espace
                    </h2>

                    <p class="text-muted mb-4">
                        Sélectionnez votre rôle pour continuer
                    </p>

                    <!-- FORM -->
                    <form method="POST" action="{{ route('role.set') }}">
                        @csrf

                        <div class="d-grid gap-3">

                            @forelse($roles as $role)
                                <button type="submit"
                                        name="role"
                                        value="{{ $role }}"
                                        class="btn btn-lg role-btn">
                                    {{ ucfirst($role) }}
                                </button>
                            @empty
                                <p class="text-center text-muted">Aucun rôle disponible.</p>
                            @endforelse

                        </div>

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