<?php

namespace App\Modules\Systeme\Services;

use App\Models\Commande;

class WhatsAppService
{
    public function waLink(Commande $commande): string
    {
        $number = $this->formatNumber($commande->whatsapp ?? $commande->telephone_client);

        if (!$number) {
            return '#';
        }

        $message = $this->buildMessage($commande);

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }

    public function formatNumber(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $clean = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($clean, '+')) {
            return $clean;
        }

        if (str_starts_with($clean, '00')) {
            return '+' . substr($clean, 2);
        }

        if (strlen($clean) === 8) {
            return '+226' . $clean;
        }

        return $clean;
    }

    public function buildMessage(Commande $commande): string
    {
        $montant = number_format((float) $commande->montant_total + (float) $commande->frais_livraison, 0, ',', ' ');

        return implode("\n", [
            "Bonjour {$commande->nom_client},",
            '',
            "Nous avons bien reçu votre commande {$commande->reference} sur K'Educ.",
            "Montant total : {$montant} FCFA",
            '',
            'Nous vous contactons concernant son traitement.',
            '',
            'Merci de votre confiance.',
            "Cabinet K'Educ",
        ]);
    }
}
