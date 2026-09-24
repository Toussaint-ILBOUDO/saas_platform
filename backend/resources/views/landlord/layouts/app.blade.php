@php
    $sections = [
        ['route' => 'landlord.dashboard', 'name' => 'landlord.dashboard', 'label' => 'Tableau de bord', 'icon' => 'bi-speedometer2'],
        ['route' => 'landlord.cabinets.index', 'name' => 'landlord.cabinets.*', 'label' => 'Cabinets', 'icon' => 'bi-buildings'],
        ['route' => 'landlord.facturation.index', 'name' => 'landlord.facturation.*', 'label' => 'Facturation', 'icon' => 'bi-receipt'],
        ['route' => 'landlord.journal.index', 'name' => 'landlord.journal.*', 'label' => 'Journal', 'icon' => 'bi-journal-text'],
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SaasCD')</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('templates/publicpages/assets/css/landlord.css') }}">
</head>

<body class="landlord-body">

    <div class="landlord-shell">

        <aside class="landlord-sidebar">
            <a class="landlord-brand" href="{{ route('landlord.dashboard') }}">
                <i class="bi bi-grid-1x2-fill"></i> Saas<span>CD</span>
            </a>

            <nav class="landlord-nav">
                @foreach ($sections as $section)
                    <a href="{{ route($section['route']) }}"
                       class="landlord-nav-link {{ request()->routeIs($section['name']) ? 'active' : '' }}">
                        <i class="bi {{ $section['icon'] }}"></i>
                        <span>{{ $section['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="landlord-main">
            <header class="landlord-topbar">
                <div class="landlord-topbar-title">@yield('title', 'SaasCD')</div>
                <form method="POST" action="{{ route('landlord.logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
                    </button>
                </form>
            </header>

            <main class="landlord-content">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

    </div>

</body>
</html>