<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabinetPublicResource extends JsonResource
{
    /**
     * @param  \App\Models\ParametrePublic  $parametres
     * @param  array  $fonctionnalites
     */
    public function __construct(
        mixed $resource,
        protected array $themes = [],
        protected array $piedDePage = [],
        protected array $donnees = []
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'nom' => $this->nom,
            'slug' => $this->id,
            'theme' => $this->themes,
            'pied_de_page' => $this->piedDePage,
            'donnees' => $this->donnees,
            'logo_url' => $this->logoUrl(),
            'fonctionnalites_actives' => $this->fonctionnalitesActives(),
        ];
    }

    protected function logoUrl(): ?string
    {
        $logo = $this->donnees['logo'] ?? null;
        if (! is_array($logo) || empty($logo['path'])) {
            return null;
        }
        $v = $logo['updated_at'] ?? null;

        return '/api/public/logo'.($v ? "?v={$v}" : '');
    }

    protected function fonctionnalitesActives(): array
    {
        $parametres = \App\Models\ParametresCabinet::where('cabinet_id', $this->id)->first();

        return $parametres?->fonctionnalites_activees ?? ['pedagogie'];
    }
}