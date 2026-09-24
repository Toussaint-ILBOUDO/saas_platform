<footer id="footer" class="footer light-background">

    @php
        $isHome = request()->is('/');
        $cabinet = config('keduc.cabinet');
        $cabinetPhone = preg_replace('/^(\+226)(\d{2})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4 $5', $cabinet['telephone']);
    @endphp

    <div class="container footer-top">

        <div class="row gy-4">

            {{-- Présentation --}}
            <div class="col-lg-4 col-md-6 footer-about">

                <a href="{{ url('/') }}" class="logo d-flex align-items-center">

                    <img
                        src="{{ asset('assets/img/logo.png') }}"
                        alt="K'Educ"
                        style="max-height:60px;"
                    >

                    <span class="sitename ms-2">
                        K'Educ
                    </span>

                </a>

                <div class="footer-contact pt-3">

                    <p>
                        K'Educ accompagne les élèves du primaire,
                        du collège, du lycée et du supérieur partout
                        au Burkina Faso grâce à un réseau de plus de
                        100 enseignants qualifiés.
                    </p>

                    <p class="mt-3">
                        <strong>Tél :</strong>
                        <span>{{ $cabinetPhone }}</span>
                    </p>

                    <p>
                        <strong>Email :</strong>
                        <span>{{ $cabinet['email'] }}</span>
                    </p>

                </div>

                <div class="social-links d-flex mt-4">
                    <a class="twitter" aria-label="Suivre K'Educ sur X"><i class="bi bi-twitter-x"></i></a>
                    <a class="facebook" aria-label="Suivre K'Educ sur Facebook"><i class="bi bi-facebook"></i></a>
                    <a class="instagram" aria-label="Suivre K'Educ sur Instagram"><i class="bi bi-instagram"></i></a>
                    <a class="linkedin" aria-label="Suivre K'Educ sur LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>

            </div>

            {{-- Navigation --}}
            <div class="col-lg-2 col-md-3 footer-links">

                <h4>Navigation</h4>

                <ul>
                    <li><a href="{{ $isHome ? '#hero' : url('/') . '#hero' }}">Accueil</a></li>
                    <li><a href="{{ $isHome ? '#about' : url('/') . '#about' }}">À propos</a></li>
                    <li><a href="{{ $isHome ? '#services' : url('/') . '#services' }}">Services</a></li>
                    <li><a href="{{ route('actualites.index') }}">Actualités</a></li>
                    <li><a href="{{ route('bibliothequepub.index') }}">Bibliothèque</a></li>
                    <li><a href="{{ $isHome ? '#contact' : url('/') . '#contact' }}">Contact</a></li>
                    <li><a href="{{ route('login') }}">Connexion</a></li>
                </ul>

            </div>

            {{-- Services --}}
            <div class="col-lg-3 col-md-3 footer-links">

                <h4>Nos services</h4>

                <ul>

                    <li>
                        <a href="{{ route('demande-cours.create') }}">
                            Cours à domicile
                        </a>
                    </li>

                    <li>
                        <span>Renforcement scolaire</span>
                    </li>

                    <li>
                        <span>Préparation aux examens</span>
                    </li>

                    <li>
                        <span>Mise à niveau</span>
                    </li>

                    <li>
                        <span>Cours en ligne</span>
                    </li>

                    <li>
                        <a href="{{ route('bibliothequepub.index') }}">
                            Bibliothèque numérique
                        </a>
                    </li>

                </ul>

            </div>

            {{-- Informations --}}
            <div class="col-lg-3 col-md-6 footer-links">

                <h4>Pourquoi K'Educ ?</h4>

                <ul>

                    <li>
                        Plus de 100 enseignants qualifiés
                    </li>

                    <li>
                        Présent dans tout le Burkina Faso
                    </li>

                    <li>
                        Suivi pédagogique personnalisé
                    </li>

                    <li>
                        Rapports mensuels
                    </li>

                    <li>
                        Facturation transparente
                    </li>

                    <li>
                        Accompagnement du primaire au supérieur
                    </li>

                </ul>

            </div>

        </div>

    </div>

    <div class="container copyright text-center mt-4">

        <p>

            © {{ date('Y') }}

            <strong class="px-1 sitename">
                K'Educ
            </strong>

            Tous droits réservés.

        </p>

        <div class="credits">

            Conçu et développé par

            <strong>
                Magis Plus Center
            </strong>

        </div>

    </div>

</footer>