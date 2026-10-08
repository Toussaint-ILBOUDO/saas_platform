<?php

namespace App\Modules\Finance\Http\Controllers\Api\Enseignant;

use App\Http\Controllers\Controller;
use App\Models\BulletinPaie;
use App\Modules\Finance\Http\Requests\ContesterBulletinApiRequest;
use App\Modules\Finance\Http\Requests\FilterBulletinRequest;
use App\Modules\Finance\Http\Resources\BulletinPaieResource;
use App\Modules\Finance\Services\BulletinPaiePdfService;
use App\Modules\Finance\Services\BulletinPaieQueryService;
use App\Modules\Finance\Services\BulletinPaieValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * T7A.9 — Bulletin de paie : côté enseignant.
 *
 * L'enseignant ne voit que SES bulletins (le query service scope à son profil
 * et 404 un bulletin étranger) et déroule son cycle : consulter, valider,
 * contester (D-052), confirmer la réception de son paiement. La machine à
 * états vit dans le service — les routes sont des transitions explicites,
 * jamais un PATCH générique sur `statut`.
 */
class BulletinPaieApiController extends Controller
{
    public function __construct(
        private readonly BulletinPaieQueryService $queries,
        private readonly BulletinPaieValidationService $validation,
        private readonly BulletinPaiePdfService $pdfs,
    ) {}

    /**
     * Index : les bulletins de l'enseignant connecté.
     */
    public function index(
        FilterBulletinRequest $request
    ): AnonymousResourceCollection {
        $bulletins = $this->queries->paginerPourEnseignant(
            (int) $request->user()->enseignantProfil->id,
            $request->validated(),
            (int) $request->integer('per_page', $request->integer('par_page', 20)),
        );

        return BulletinPaieResource::collection($bulletins);
    }

    /**
     * Détail enseignant : 404 si le bulletin n'est pas le sien.
     */
    public function show(Request $request): BulletinPaieResource
    {
        $bulletin = $this->queries->trouverPourEnseignant(
            (int) $request->user()->enseignantProfil->id,
            (int) $request->route('bulletin'),
        );

        return BulletinPaieResource::make($this->detail($bulletin));
    }

    /**
     * PDF enseignant : même périmètre strict que le détail.
     */
    public function pdf(Request $request): Response
    {
        $bulletin = $this->queries->trouverPourEnseignant(
            (int) $request->user()->enseignantProfil->id,
            (int) $request->route('bulletin'),
        );

        return $this->pdfs->stream($this->detail($bulletin));
    }

    /**
     * L'enseignant marque le bulletin comme consulté (genere → consulte).
     */
    public function consulter(BulletinPaie $bulletin): BulletinPaieResource
    {
        $this->authorize('update', $bulletin);

        return BulletinPaieResource::make(
            $this->detail($this->validation->consulter($bulletin))
        );
    }

    /**
     * L'enseignant valide son bulletin (consulte → valide).
     */
    public function valider(BulletinPaie $bulletin): BulletinPaieResource
    {
        $this->authorize('update', $bulletin);

        return BulletinPaieResource::make(
            $this->detail($this->validation->valider($bulletin))
        );
    }

    /**
     * L'enseignant conteste son bulletin (consulte → conteste, D-052).
     *
     * La catégorie du motif est contrôlée ici et revalidée par le service.
     */
    public function contester(
        ContesterBulletinApiRequest $request,
        BulletinPaie $bulletin
    ): BulletinPaieResource {
        $bulletin = $this->validation->contester(
            $bulletin,
            $request->validated('motif_contestation'),
            $request->validated('commentaire_enseignant'),
        );

        return BulletinPaieResource::make($this->detail($bulletin));
    }

    /**
     * L'enseignant confirme avoir reçu le paiement (verse → reçu, D-052).
     */
    public function confirmerReception(BulletinPaie $bulletin): BulletinPaieResource
    {
        $this->authorize('update', $bulletin);

        return BulletinPaieResource::make(
            $this->detail($this->validation->confirmerReception($bulletin))
        );
    }

    /**
     * Charge la profondeur nécessaire au détail d'un bulletin.
     */
    private function detail(BulletinPaie $bulletin): BulletinPaie
    {
        return $bulletin->load([
            'enseignant.user',
            'periode',
            'lignes.eleve.user',
            'lignes.matiere',
            'ajustements',
            'recaperePar',
        ]);
    }
}