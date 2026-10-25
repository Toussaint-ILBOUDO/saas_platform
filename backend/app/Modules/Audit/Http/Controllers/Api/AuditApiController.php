<?php

namespace App\Modules\Audit\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\HistoriqueActiviteResource;
use App\Models\HistoriqueActivite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Journal d'audit du cabinet (M13) — lecture seule, rôle admin_cabinet.
 */
class AuditApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = HistoriqueActivite::query()->with('utilisateur');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('level')) {
            $query->where('level', $request->string('level')->toString());
        }

        if ($request->filled('utilisateur_id')) {
            $query->where('user_id', $request->integer('utilisateur_id'));
        }

        if ($request->filled('entite_type')) {
            $query->where('entite_type', $request->string('entite_type')->toString());
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($b) => $b
                ->where('action', 'ilike', "%{$q}%")
                ->orWhere('description', 'ilike', "%{$q}%"));
        }

        if ($request->filled('du')) {
            $query->whereDate('created_at', '>=', $request->string('du')->toString());
        }

        if ($request->filled('au')) {
            $query->whereDate('created_at', '<=', $request->string('au')->toString());
        }

        $parPage = (int) $request->integer('per_page', $request->integer('par_page', 25));

        $activites = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min(max($parPage, 1), 100));

        return HistoriqueActiviteResource::collection($activites);
    }

    public function actions(): JsonResponse
    {
        $actions = HistoriqueActivite::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json(['data' => $actions]);
    }
}
