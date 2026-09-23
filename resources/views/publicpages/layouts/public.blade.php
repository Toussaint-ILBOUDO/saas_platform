<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', '')">
    <meta name="keywords" content="@yield('meta_keywords', '')">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Meta additionnelles (Open Graph, Twitter Cards...) --}}
    @stack('meta')

    {{-- Favicons --}}
    <link href="{{ asset('templates/publicpages/assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('templates/publicpages/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    {{-- Fonts --}}
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    {{-- Vendor CSS --}}
    <link href="{{ asset('templates/publicpages/assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/publicpages/assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/publicpages/assets/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/publicpages/assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/publicpages/assets/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/publicpages/assets/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">
    

    {{-- Main CSS --}}
    <link href="{{ asset('templates/publicpages/assets/css/main.css') }}" rel="stylesheet">

    {{-- Librairie CSS (chargé globalement pour le panier flottant) --}}
    <link rel="stylesheet" href="{{ asset('assetLibrairie/css/librairie.css') }}">

    @stack('styles')
</head>

<body class="@yield('body_class', 'index-page')">

    {{-- Navbar --}}
   @include('publicpages.components.navbar')

    {{-- Toast container pour les notifications du panier --}}
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    {{-- Panier flottant (draggable) --}}
    <div id="lib-floating-cart" class="lib-floating-cart" title="Voir mon panier">
        <a href="{{ route('librairie.panier') }}" class="lib-floating-cart-link" aria-label="Voir mon panier">
            <i class="bi bi-cart-fill"></i>
            <span id="floating-cart-badge" class="lib-floating-cart-badge bg-danger text-white" style="display:none;">0</span>
        </a>
        <div class="lib-floating-cart-handle"><i class="bi bi-grip-vertical"></i></div>
    </div>

    {{-- Contenu principal --}}
    <main class="main">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('publicpages.components.footer')

    {{-- Scroll Top --}}
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    {{-- Preloader --}}
    <div id="preloader"></div>

    {{-- Vendor JS --}}
    <script src="{{ asset('templates/publicpages/assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('templates/publicpages/assets/vendor/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('templates/publicpages/assets/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('templates/publicpages/assets/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('templates/publicpages/assets/vendor/purecounter/purecounter_vanilla.js') }}"></script>
    <script src="{{ asset('templates/publicpages/assets/vendor/swiper/swiper-bundle.min.js') }}"></script>

    {{-- Main JS --}}
    <script src="{{ asset('templates/publicpages/assets/js/main.js') }}"></script>

    {{-- Librairie Cart JS (module panier) --}}
    <script src="{{ asset('assetLibrairie/js/librairie-cart.js') }}"></script>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener("click", function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute("href"))
                    ?.scrollIntoView({ behavior: "smooth" });
            });
        });
    </script>

    @stack('scripts')

</body>
</html>
