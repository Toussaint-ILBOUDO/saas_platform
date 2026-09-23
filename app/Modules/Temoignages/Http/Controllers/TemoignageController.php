<?php

namespace App\Modules\Temoignages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Temoignage;
use App\Modules\Temoignages\Http\Requests\StoreTemoignageRequest;
use App\Modules\Temoignages\Http\Requests\UpdateTemoignageRequest;
use App\Modules\Temoignages\Services\TemoignageNotificationService;
use App\Modules\Temoignages\Services\TemoignageService;

class TemoignageController extends Controller
{
    public function __construct(
        protected TemoignageService $service,
        protected TemoignageNotificationService $notifications
    ) {}

    public function mes()
    {
        $temoignages = $this->service->listeMes(auth()->id());

        return view('temoignages.mes-temoignages', compact('temoignages'));
    }

    public function create()
    {
        $this->authorize('create', Temoignage::class);

        return view('temoignages.creer');
    }

    public function store(StoreTemoignageRequest $request)
    {
        $temoignage = $this->service->create(
            $request->validated(),
            $request->user()
        );

        $this->notifications->nouveau($temoignage);

        return redirect()
            ->route('temoignages.mes.index')
            ->with('success', 'Votre témoignage a été publié avec succès.');
    }

    public function edit(Temoignage $temoignage)
    {
        $this->authorize('update', $temoignage);

        return view('temoignages.modifier', compact('temoignage'));
    }

    public function update(UpdateTemoignageRequest $request, Temoignage $temoignage)
    {
        $this->service->update($temoignage, $request->validated());

        return redirect()
            ->route('temoignages.mes.index')
            ->with('success', 'Votre témoignage a été modifié.');
    }

    public function destroy(Temoignage $temoignage)
    {
        $this->authorize('delete', $temoignage);

        $this->service->delete($temoignage);

        return redirect()
            ->route('temoignages.mes.index')
            ->with('success', 'Votre témoignage a été supprimé.');
    }
}
