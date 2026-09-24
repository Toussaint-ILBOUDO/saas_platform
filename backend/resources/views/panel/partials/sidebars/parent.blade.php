<aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
      <div class="sidebar-header">
         <a class="brand-mark" href="{{ url('/') }}" aria-label="K'Educ dashboard">
            <span>
                <img
                    src="{{ asset('assets/img/logo.png') }}"
                    alt="K'Educ Logo"
                    style="width: 60px; height: 60px; object-fit: contain;"
                >
            </span>

            <span class="brand-copy">
                <span class="brand-title">K'Educ</span>
                <span class="brand-subtitle">L'école pour tous les âges</span>
            </span>
        </a>
      </div>

      <nav class="sidebar-nav">
        <a class="nav-link active" href="{{ route('dashboard') }}" aria-current="page">
          <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
          <span class="nav-text">Dashboard</span>
        </a>

        <a class="nav-link" href="{{ route('actualites.internes') }}">
            <span class="nav-icon"><i class="bi bi-megaphone" aria-hidden="true"></i></span>
            <span class="nav-text">Actualités</span>
        </a>

        <a class="nav-link" href="{{ route('mes-enfants') }}">
            <span class="nav-icon"><i class="bi bi-heart" aria-hidden="true"></i></span>
            <span class="nav-text">Mes enfants</span>
        </a>

        <span class="nav-link">
            <span class="nav-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
            <span class="nav-text">Progression</span>
        </span>

        <a class="nav-link" href="{{ route('objectifs-pedagogiques.index') }}">
            <span class="nav-icon"><i class="bi bi-bullseye" aria-hidden="true"></i></span>
            <span class="nav-text">Objectifs</span>
        </a>

        <a class="nav-link" href="{{ route('planning.index') }}">
            <span class="nav-icon"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
            <span class="nav-text">Planning</span>
        </a>

        <a class="nav-link" href="{{ route('mes-factures.index') }}">
            <span class="nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
            <span class="nav-text">Factures</span>
        </a>

        <a class="nav-link" href="{{ route('mes-factures.index', ['statut' => 'payee']) }}">
            <span class="nav-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <span class="nav-text">Paiements</span>
        </a>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-library" aria-hidden="true"></i></span>
                <span class="nav-text">Bibliothèque</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('bibliothequepub.index') }}"><i class="bi bi-collection"></i> Tous les documents</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('bibliotheque.favoris') }}"><i class="bi bi-heart"></i> Mes favoris</a></li>
                @can('create', \App\Models\DocumentBibliotheque::class)
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('bibliotheque.index') }}"><i class="bi bi-folder2-open"></i> Mes documents</a></li>
                @endcan
            </ul>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
                <span class="nav-text">Librairie</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('librairie.index') }}"><i class="bi bi-bag"></i> Boutique</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('librairie.mes-commandes.index') }}"><i class="bi bi-receipt"></i> Mes commandes</a></li>
            </ul>
        </li>

        <a class="nav-link" href="{{ route('notifications.index') }}">
            <span class="nav-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <span class="nav-text">Notifications</span>
        </a>

        <a class="nav-link" href="{{ route('profil.edit') }}">
            <span class="nav-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
            <span class="nav-text">Mon profil</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <span class="status-dot"></span>
        <span class="sidebar-footer-text">L'école pour tous les âges !</span>
      </div>
    </aside>
