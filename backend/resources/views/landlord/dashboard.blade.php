<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | SaasCD</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/landlord.css') }}">
</head>

<body class="landlord-body">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('landlord.dashboard') }}">SaasCD</a>
        <span class="navbar-text text-light small me-auto ms-3">Administration de la plateforme</span>

        <form method="POST" action="{{ route('landlord.logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm">Déconnexion</button>
        </form>
    </div>
</nav>

<main class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 fw-bold">Tableau de bord</h1>
            <p class="text-secondary">
                Bienvenue, {{ auth('landlord')->user()->nom }}. Le socle Landlord arrive avec les tâches T2.2 à T2.10.
            </p>

            <div class="alert alert-info">
                <strong>En construction :</strong> gestion des cabinets (T2.3), pipeline de création (T2.4),
                suspension/suppression (T2.5), impersonation (T2.6), journal de la plateforme (T2.7).
            </div>
        </div>
    </div>
</main>

</body>
</html>