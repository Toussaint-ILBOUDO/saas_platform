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
                <span class="nav-icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span>
                <span class="nav-text">Référentiels</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('classes.index') }}"><i class="bi bi-grid-1x2"></i> Classes</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('matieres.index') }}"><i class="bi bi-book"></i> Matières</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('type-cours.index') }}"><i class="bi bi-diagram-3"></i> Types de cours</a></li>
            </ul>
        </li>

        <a class="nav-link" href="{{ route('demande-cours.index') }}">
            <span class="nav-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
            <span class="nav-text">Demandes de cours</span>
        </a>

        <a class="nav-link" href="{{ route('contrats.index') }}">
            <span class="nav-icon"><i class="bi bi-file-text" aria-hidden="true"></i></span>
            <span class="nav-text">Contrats</span>
        </a>

        <a class="nav-link" href="{{ route('cahiers-textes.index') }}">
            <span class="nav-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
            <span class="nav-text">Cahiers de texte</span>
        </a>

        <a class="nav-link" href="{{ route('rapports-mensuels.index') }}">
            <span class="nav-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
            <span class="nav-text">Rapports enseignants</span>
        </a>

        <a class="nav-link" href="{{ route('objectifs-pedagogiques.index') }}">
            <span class="nav-icon"><i class="bi bi-bullseye" aria-hidden="true"></i></span>
            <span class="nav-text">Objectifs pédagogiques</span>
        </a>

        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                <span class="nav-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                <span class="nav-text">Finance</span>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('finance.periodes.index') }}"><i class="bi bi-bar-chart-line"></i> Périodes comptables</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('finance.factures.index') }}"><i class="bi bi-receipt"></i> Factures clients</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('finance.bulletins-paie.index') }}"><i class="bi bi-cash-coin"></i> Bulletins de paie</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('finance.type-ajustements.index') }}"><i class="bi bi-tags"></i> Types d'ajustements</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('finance.facture-cabinet.index') }}"><i class="bi bi-briefcase"></i> Facturation cabinet</a></li>
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
      </nav>

      <div class="sidebar-footer">
        <span class="status-dot"></span>
        <span class="sidebar-footer-text">L'école pour tous les âges !</span>
      </div>
    </aside>
