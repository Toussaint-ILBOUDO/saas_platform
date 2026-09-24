<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Modules\Pedagogie\Services\DemandeCoursService;
use App\Models\CahierTexte;
use App\Policies\CahierTextePolicy;
use App\Models\BulletinPaie;
use App\Models\RapportMensuelEnseignant;
use App\Models\ObjectifPedagogique;
use App\Policies\BulletinPaiePolicy;
use App\Policies\RapportMensuelEnseignantPolicy;
use App\Policies\ObjectifPedagogiquePolicy;
use App\Models\DocumentBibliotheque;
use App\Policies\DocumentBibliothequePolicy;
use App\Models\FactureCabinet;
use App\Policies\FactureCabinetPolicy;
use App\Models\Commande;
use App\Policies\CommandePolicy;
use App\Models\FaqSection;
use App\Models\FaqQuestion;
use App\Models\Actualite;
use App\Policies\FaqPolicy;
use App\Policies\ActualitePolicy;
use App\Modules\Communication\Services\FaqService;
use App\Modules\Communication\Services\ActualiteService;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DemandeCoursService::class,
            \App\Modules\Pedagogie\Services\DemandeCoursService::class
        );

        $this->app->bind(
            FaqService::class,
            FaqService::class
        );

        $this->app->bind(
            ActualiteService::class,
            ActualiteService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('panel.*', function ($view) {

            if (!Auth::check()) {
                return;
            }

            $notificationsMenu = Notification::query()
                ->where('user_id', Auth::id())
                ->latest()
                ->take(5)
                ->get();

            $notificationsUnread = Notification::query()
                ->where('user_id', Auth::id())
                ->where('lu', false)
                ->count();

            $view->with([
                'notificationsMenu' => $notificationsMenu,
                'notificationsUnread' => $notificationsUnread,
            ]);
        });

        Gate::policy(
            CahierTexte::class,
            CahierTextePolicy::class
        );

        Gate::policy(
            RapportMensuelEnseignant::class,
            RapportMensuelEnseignantPolicy::class
        );

        Gate::policy(
            BulletinPaie::class,
            BulletinPaiePolicy::class
        );

        Gate::policy(
            ObjectifPedagogique::class,
            ObjectifPedagogiquePolicy::class
        );

        Gate::policy(
            DocumentBibliotheque::class,
            DocumentBibliothequePolicy::class
        );

        Gate::policy(
            FactureCabinet::class,
            FactureCabinetPolicy::class
        );

        Gate::policy(
            Commande::class,
            CommandePolicy::class
        );

        Gate::policy(
            FaqSection::class,
            FaqPolicy::class
        );

        Gate::policy(
            FaqQuestion::class,
            FaqPolicy::class
        );

        Gate::policy(
            Actualite::class,
            ActualitePolicy::class
        );
    }
}
