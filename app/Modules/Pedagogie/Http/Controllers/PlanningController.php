<?php

namespace App\Modules\Pedagogie\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pedagogie\Services\CahierTexteService;
use App\Modules\Pedagogie\Services\PlanningCoursService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanningController extends Controller
{
    public function __construct(
        private readonly CahierTexteService $service,
        private readonly PlanningCoursService $planningService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();

        $weekOffset = (int) $request->integer('semaine', 0);

        $monday = Carbon::now()->startOfWeek()->addWeeks($weekOffset);
        $sunday = $monday->copy()->endOfWeek();

        if ($user->hasRole('parent')) {
            $eleves = $user->enfants()->with('user')->get();

            if ($request->integer('eleve') > 0) {
                $eleves = $eleves->where('id', $request->integer('eleve'));
            }

            $sessions = $this->service->getWeekForEleves($eleves, $monday, $sunday);
            $isParent = true;
            $eleveIds = $eleves->pluck('id')->all();
        } else {
            $eleve = $user->eleve;

            abort_unless($eleve, 403);

            $sessions = $this->service->getWeekForEleve($eleve, $monday, $sunday);
            $isParent = false;
            $eleveIds = [$eleve->id];
        }

        $creneaux = $this->planningService->listForEleves($eleveIds);

        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $monday->copy()->addDays($i);

            $weekDays[$i] = [
                'date' => $day,
                'sessions' => $sessions
                    ->filter(fn ($cahier) => $cahier->date_seance->isSameDay($day))
                    ->values(),
            ];
        }

        return view('pedagogie.planning.index', [
            'weekDays' => $weekDays,
            'weekOffset' => $weekOffset,
            'start' => $monday,
            'end' => $sunday,
            'isParent' => $isParent,
            'creneaux' => $creneaux,
        ]);
    }
}