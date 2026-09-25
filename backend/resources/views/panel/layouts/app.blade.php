@php
    $role = auth()->check()
        ? (auth()->user()->getRoleNames()->first() ?? 'default')
        : 'guest';

    $knownSidebars = ['admin', 'default', 'eleve', 'enseignant', 'gestionnaire', 'parent', 'super-admin'];
    $sidebarRole = in_array($role, $knownSidebars, true) ? $role : 'default';
@endphp

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Keduc')</title>

    <link rel="stylesheet" href="{{ asset('adminpanel/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminpanel/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('adminpanel/assets/css/style.css') }}">

    @stack('styles')

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
</head>

<body>

{{-- Impersonation Landlord (T2.6) : bannière + sortie --}}
@include('panel.partials.impersonation-banniere')

<div class="admin-shell">

    <div class="sidebar-backdrop" data-sidebar-close></div>

    {{-- SIDEBAR DYNAMIQUE PAR RÔLE (SAFE) --}}
    @include('panel.partials.sidebars.' . $sidebarRole)

    <div class="admin-main">

        @include('panel.partials.navbar')

        <main class="dashboard-content">
            @yield('content')
        </main>

        @include('panel.partials.footer')

    </div>
</div>

<script src="{{ asset('adminpanel/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('adminpanel/assets/js/main.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

@stack('scripts')

</body>
</html>