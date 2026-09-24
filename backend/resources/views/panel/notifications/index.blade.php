@extends('panel.layouts.app')

@section('title', 'Notifications')

@section('content')

<div class="container-fluid">

    <div class="page-heading">

        <div class="page-heading-copy">

            <div class="page-icon">
                <i class="bi bi-bell"></i>
            </div>

            <div>

                <h1 class="mb-0">
                    Notifications
                </h1>

                <p class="text-muted mb-0">
                    Historique de vos notifications
                </p>

            </div>

        </div>

        @if($notifications->total() > 0 && $notificationsUnread > 0)
            <div class="page-heading-actions">
                <form method="POST" action="{{ route('notifications.markAllAsRead') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-check-all me-1"></i>
                        Tout marquer comme lu
                    </button>
                </form>
            </div>
        @endif

    </div>

    @if($notifications->isEmpty())

        <div class="panel">
            <div class="notif-empty">
                <div class="notif-empty-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <h5 class="notif-empty-title">
                    Vous êtes à jour
                </h5>
                <p class="notif-empty-text text-muted mb-0">
                    Aucune notification pour le moment.
                </p>
            </div>
        </div>

    @else

        <div class="notif-list">

            @foreach($notifications as $notification)

                <div class="notif-card {{ $notification->lu ? 'notif-card-lu' : 'notif-card-non-lu' }}">

                    <div class="notif-card-icon {{ $notification->couleur }}">
                        <i class="bi {{ $notification->icone_html }}"></i>
                    </div>

                    <div class="notif-card-body">

                        <div class="notif-card-header">

                            <h6 class="notif-card-title mb-0">
                                {{ $notification->titre }}
                            </h6>

                            <div class="notif-card-meta">

                                @if(!$notification->lu)
                                    <span class="badge text-bg-primary notif-badge-new">
                                        Nouveau
                                    </span>
                                @endif

                                <span class="notif-card-time">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>

                            </div>

                        </div>

                        <p class="notif-card-text text-muted mb-2">
                            {{ $notification->contenu }}
                        </p>

                        <div class="notif-card-actions">

                            @if($notification->url && $notification->action_label)
                                <a
                                    href="{{ $notification->url }}"
                                    class="btn btn-sm btn-primary"
                                >
                                    <i class="bi {{ $notification->icone_html }} me-1"></i>
                                    {{ $notification->action_label }}
                                </a>
                            @endif

                            @if(!$notification->lu)
                                <form
                                    method="POST"
                                    action="{{ route('notifications.markAsRead', $notification) }}"
                                    class="d-inline"
                                >
                                    @csrf
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Marquer comme lu"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('notifications.destroy', $notification) }}"
                                class="d-inline"
                                onsubmit="return confirm('Supprimer cette notification ?')"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Supprimer"
                                >
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>

    @endif

</div>

@endsection
