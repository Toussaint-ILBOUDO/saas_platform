@php
    $isHome = request()->is('/');
    $user = auth()->user();
    $cabinet = \App\Support\CabinetInfo::all();
    $cabinetPhone = preg_replace('/^(\+226)(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $cabinet['telephone']);
    $cabinetWhatsapp = '+226 ' . preg_replace('/^(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4', $cabinet['whatsapp']);
@endphp

<header id="header" class="header sticky-top">

    {{-- Top Bar --}}
    <div class="topbar d-flex align-items-center">
        <div class="container d-flex justify-content-center justify-content-md-between">

            <div class="contact-info d-flex align-items-center">

                <i class="bi bi-envelope d-flex align-items-center">
                    <a href="mailto:{{ $cabinet['email'] }}">
                        {{ $cabinet['email'] }}
                    </a>
                </i>

                <i class="bi bi-phone d-flex align-items-center ms-4">
                    <span>{{ $cabinetPhone }}</span>
                </i>

                <i class="bi bi-phone d-flex align-items-center ms-4">
                    <span>{{ $cabinetWhatsapp }}</span>
                </i>

            </div>

            <div class="social-links d-none d-md-flex align-items-center">
                <a class="twitter" aria-label="Suivre K'Educ sur X"><i class="bi bi-twitter-x"></i></a>
                <a class="facebook" aria-label="Suivre K'Educ sur Facebook"><i class="bi bi-facebook"></i></a>
                <a class="instagram" aria-label="Suivre K'Educ sur Instagram"><i class="bi bi-instagram"></i></a>
                <a class="linkedin" aria-label="Suivre K'Educ sur LinkedIn"><i class="bi bi-linkedin"></i></a>
            </div>

        </div>
    </div>
    {{-- /Top Bar --}}

    {{-- Branding & Navigation --}}
    <div class="branding d-flex align-items-center">

        <div class="container position-relative d-flex align-items-center justify-content-between">

            {{-- LOGO + NOM (aligné légèrement à gauche) --}}
            <a href="{{ url('/') }}" class="logo d-flex align-items-center me-auto" style="gap:10px;">

                <img
                    src="{{ asset('assets/img/logo.png') }}"
                    alt="K'Educ"
                    style="max-height:55px;"
                >

                <div style="line-height:1.1;">
                    <span class="sitename mb-0">K'Educ</span>
                    <small class="text-muted d-block">
                        L'école pour tous les âges !
                    </small>
                </div>

            </a>

            <nav id="navmenu" class="navmenu">

                <ul>

                    {{-- Accueil --}}
                    <li>
                        <a href="{{ $isHome ? '#hero' : url('/') . '#hero' }}">
                            Accueil
                        </a>
                    </li>

                    {{-- À propos --}}
                    <li>
                        <a href="{{ $isHome ? '#about' : url('/') . '#about' }}">
                            À propos
                        </a>
                    </li>

                    {{-- Services --}}
                    <li>
                        <a href="{{ $isHome ? '#services' : url('/') . '#services' }}">
                            Services
                        </a>
                    </li>

                    {{-- Bibliothèque --}}
                    <li>
                        <a href="{{ route('bibliothequepub.index') }}">
                            Bibliothèque
                        </a>
                    </li>

                    {{-- Actualités --}}
                    <li>
                        <a href="{{ route('actualites.index') }}">
                            Actualités
                        </a>
                    </li>

                    {{-- Témoignages --}}
                    <li>
                        <a href="{{ route('temoignages.index') }}">
                            Témoignages
                        </a>
                    </li>

                    {{-- Librairie --}}
                    <li>
                        <a href="{{ route('librairie.index') }}">
                            Boutique
                        </a>
                    </li>

                    {{-- Départements --}}
                    <li>
                        <a href="{{ $isHome ? '#departments' : url('/') . '#departments' }}">
                            Départements
                        </a>
                    </li>

                    {{-- Enseignants --}}
                    <li>
                        <a href="{{ $isHome ? '#doctors' : url('/') . '#doctors' }}">
                            Nos Enseignants
                        </a>
                    </li>

                    {{-- Contact --}}
                    <li>
                        <a href="{{ $isHome ? '#contact' : url('/') . '#contact' }}">
                            Contact
                        </a>
                    </li>

                </ul>

                <i class="mobile-nav-toggle d-xl-none bi bi-list"
                   role="button" tabindex="0" aria-label="Ouvrir le menu"></i>

            </nav>

            <div class="d-flex align-items-center gap-2">

                {{-- Panier Librairie --}}
                <a href="{{ route('librairie.panier') }}" class="lib-cart-link" title="Voir mon panier">
                    <i class="bi bi-cart"></i>
                    <span id="cart-badge" class="lib-cart-badge bg-danger text-white" style="display:none;">0</span>
                </a>

                @auth
                    {{-- Utilisateur connecté : dropdown profil --}}
                    <div class="dropdown">
                        <button class="btn btn-outline-primary d-none d-sm-flex align-items-center gap-2 dropdown-toggle"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ $user->photo_profil_url }}"
                                 alt="{{ $user->prenom }}"
                                 style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                            <span class="d-none d-lg-inline">{{ $user->prenom }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <div class="dropdown-item-text">
                                    <strong>{{ $user->prenom }} {{ $user->nom }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $user->email }}</small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profil.edit') }}">
                                    <i class="bi bi-person me-2"></i>Mon profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('bibliotheque.favoris') }}">
                                    <i class="bi bi-heart me-2"></i>Mes favoris
                                </a>
                            </li>
                            @can('create', \App\Models\DocumentBibliotheque::class)
                                <li>
                                    <a class="dropdown-item" href="{{ route('bibliotheque.index') }}">
                                        <i class="bi bi-folder2-open me-2"></i>Mes documents
                                    </a>
                                </li>
                            @endcan
                            @can('create', \App\Models\Temoignage::class)
                                <li>
                                    <a class="dropdown-item" href="{{ route('temoignages.mes.index') }}">
                                        <i class="bi bi-chat-quote me-2"></i>Mes témoignages
                                    </a>
                                </li>
                            @endcan
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2 me-2"></i>Espace de travail
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    {{-- Visiteur : Connexion --}}
                    <a class="btn btn-outline-primary d-none d-sm-block" href="{{ route('login') }}">
                        Connexion
                    </a>
                @endauth

                <a class="cta-btn d-none d-sm-block" href="{{ route('demande-cours.create') }}">
                    Demander un cours
                </a>

                <a
                    href="https://wa.me/226{{ $cabinet['whatsapp'] }}"
                    target="_blank"
                    class="btn btn-success d-none d-md-block"
                    style="display:flex; align-items:center; gap:5px;"
                    aria-label="Contacter K'Educ sur WhatsApp"
                >
                    <i class="bi bi-whatsapp"></i>
                </a>

            </div>

        </div>

    </div>

</header>