@php
    $user = auth()->user();
    $roles = $user->getRoleNames();
@endphp

<nav class="navbar admin-navbar navbar-expand bg-white">

    <div class="container-fluid px-3 px-lg-4">

        <button
            class="sidebar-toggle"
            type="button"
            data-sidebar-toggle
            aria-controls="adminSidebar"
            aria-expanded="true"
            aria-label="Toggle sidebar"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        {{-- Recherche --}}
        <form class="d-none d-md-flex ms-3 flex-grow-1" role="search">
            <input
                class="form-control search-input"
                type="search"
                placeholder="Rechercher..."
                aria-label="Search"
            >
        </form>

        <div class="navbar-actions ms-auto">

            {{-- Dark Mode --}}
            <button
                class="icon-button theme-toggle"
                type="button"
                data-theme-toggle
                aria-label="Switch color theme"
                title="Switch color theme"
            >
                <i
                    class="bi bi-moon-stars"
                    data-theme-icon
                    aria-hidden="true"
                ></i>
            </button>

            {{-- Notifications --}}
           <div class="dropdown">

                <button
                    class="icon-button"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notifications"
                >

                    @if($notificationsUnread > 0)
                        <span class="notification-dot"></span>
                    @endif

                    <i class="bi bi-bell"></i>

                </button>

                <div class="dropdown-menu dropdown-menu-end notification-menu">

                    <div class="dropdown-header d-flex justify-content-between align-items-center">

                        <span class="fw-bold">
                            Notifications
                        </span>

                        @if($notificationsUnread)
                            <span class="badge text-bg-danger">
                                {{ $notificationsUnread }}
                            </span>
                        @endif

                    </div>

                    @forelse($notificationsMenu as $notification)

                        <a
                            href="{{ route('notifications.show', $notification) }}"
                            class="dropdown-item notif-dropdown-item {{ $notification->lu ? '' : 'notif-dropdown-item-unread' }}"
                        >

                            <span class="notif-dropdown-icon {{ $notification->couleur }}">
                                <i class="bi {{ $notification->icone_html }}"></i>
                            </span>

                            <span class="notif-dropdown-content">

                                <div class="notif-dropdown-title">

                                    {{ $notification->titre }}

                                    @if(!$notification->lu)
                                        <span class="notif-dropdown-dot"></span>
                                    @endif

                                </div>

                                <small class="text-muted notif-dropdown-text">
                                    {{ Str::limit($notification->contenu, 55) }}
                                </small>

                                <div class="notif-dropdown-time">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>

                            </span>

                        </a>

                    @empty

                        <div class="notif-dropdown-empty">
                            <div class="notif-dropdown-empty-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="text-muted small">
                                Aucune notification
                            </div>
                        </div>

                    @endforelse

                    <div class="dropdown-divider"></div>

                    <a
                        href="{{ route('notifications.index') }}"
                        class="dropdown-item text-center fw-bold notif-dropdown-all"
                    >
                        Voir toutes les notifications
                    </a>

                </div>

            </div>

            {{-- Profil utilisateur --}}
            <div class="dropdown">

                <button
                    class="profile-button dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >

                    <img
                        class="avatar-img avatar-sm"
                        src="{{ asset('adminpanel/assets/images/avatar/avatar.jpg') }}"
                        alt="{{ $user->prenom }}"
                    >

                    <span class="profile-name d-none d-sm-inline">
                        {{ $user->prenom }} {{ $user->nom }}
                    </span>

                </button>
                <ul class="dropdown-menu dropdown-menu-end">

                    {{-- Nom --}}
                    <li>
                        <div class="dropdown-item-text">
                            <strong>
                                {{ $user->prenom }} {{ $user->nom }}
                            </strong>
                            <br>
                            <small class="text-muted">
                                {{ $user->email }}
                            </small>
                        </div>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    {{-- Rôle actif --}}
                    <li>
                        <div class="dropdown-item-text">

                            <small class="text-muted">
                                Rôle actif
                            </small>

                            <br>

                            <strong>
                                {{ ucfirst(session('active_role')) }}
                            </strong>

                        </div>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    {{-- Profil --}}
                    <li>
                        <a
                            class="dropdown-item"
                            href="{{ route('profil.edit') }}"
                        >
                            <i class="bi bi-person me-2"></i>
                            Mon profil
                        </a>
                    </li>

                    {{-- Changer de rôle --}}
                    @if($roles->count() > 1)
                        <li>
                            <a
                                class="dropdown-item"
                                href="{{ route('role.select') }}"
                            >
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Changer de rôle
                            </a>
                        </li>
                    @endif

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    {{-- Déconnexion --}}
                    <li>

                        <form
                            action="{{ route('logout') }}"
                            method="POST"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="dropdown-item text-danger"
                            >
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Déconnexion
                            </button>

                        </form>

                    </li>

                </ul>

            </div>

        </div>

    </div>

</nav>
