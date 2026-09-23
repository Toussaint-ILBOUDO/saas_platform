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
