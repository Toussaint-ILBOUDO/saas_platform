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

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                <span class="nav-text">Utilisateurs</span>
            </a>
            <ul class="dropdown-menu">
                <li><span class="dropdown-item d-flex align-items-center gap-2"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Tous les utilisateurs</span></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('parents.index') }}"><i class="bi bi-people-fill"></i> Parents</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('eleves.index') }}"><i class="bi bi-mortarboard-fill"></i> Élèves</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('enseignants.index') }}"><i class="bi bi-person-workspace"></i> Enseignants</a></li>
            </ul>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-library" aria-hidden="true"></i></span>
                <span class="nav-text">Bibliothèque</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('bibliothequepub.index') }}"><i class="bi bi-collection"></i> Tous les documents</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.bibliotheque.index') }}"><i class="bi bi-file-earmark-text"></i> Documents</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.bibliotheque.type-documents.index') }}"><i class="bi bi-file-earmark-medical"></i> Types de document</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.bibliotheque.periodes.index') }}"><i class="bi bi-calendar3"></i> Périodes</a></li>
                <li><hr class="dropdown-divider"></li>
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
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.librairie.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.librairie.categories.index') }}"><i class="bi bi-folder2"></i> Catégories</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.librairie.produits.index') }}"><i class="bi bi-box-seam"></i> Produits</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.librairie.commandes.index') }}"><i class="bi bi-receipt"></i> Commandes</a></li>
            </ul>
        </li>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-newspaper" aria-hidden="true"></i></span>
                <span class="nav-text">Contenus</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.actualites.index') }}"><i class="bi bi-megaphone"></i> Actualités</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.temoignages.index') }}"><i class="bi bi-chat-quote"></i> Témoignages</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.faq.sections.index') }}"><i class="bi bi-question-circle"></i> FAQ</a></li>
            </ul>
        </li>

        <a class="nav-link" href="{{ route('notifications.index') }}">
            <span class="nav-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <span class="nav-text">Notifications</span>
        </a>

        <span class="nav-link">
            <span class="nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
            <span class="nav-text">Paramètres</span>
        </span>

        <span class="nav-link">
            <span class="nav-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <span class="nav-text">Audit</span>
        </span>
      </nav>

      <div class="sidebar-footer">
        <span class="status-dot"></span>
        <span class="sidebar-footer-text">L'école pour tous les âges !</span>
      </div>
    </aside>
