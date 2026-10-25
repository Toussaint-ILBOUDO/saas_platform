<?php

namespace Database\Seeders;

use App\Models\LigneTypePlateforme;
use App\Models\ParametresPlateforme;
use App\Models\ServicePlateforme;
use Illuminate\Database\Seeder;

/**
 * P8 — Catalogue initial de la plateforme (idempotent) :
 *
 * - paramètre `plateforme` : identité de la société éditrice
 *   (Magis Plus Center) affichée dans les PDF et la console ;
 * - `services_plateforme` : les fonctionnalités facturables du contrat
 *   commercial, avec leur explication (modifiable depuis la console) ;
 * - `lignes_types_plateforme` : briques réutilisables pour les factures
 *   manuelles (libellé + prix de départ + texte explicatif).
 */
class CataloguePlateformeSeeder extends Seeder
{
    public function run(): void
    {
        ParametresPlateforme::definir('plateforme', [
            'nom' => 'Magis Plus Center',
            'tagline' => "Plateforme K'Educ — gestion des cabinets de soutien scolaire",
            'contact' => 'Magis Plus Center — support commercial',
            'logo' => null,
        ]);

        $services = [
            [
                'cle' => 'frais_creation_contrat',
                'categorie' => 'ouverture',
                'libelle' => 'Frais de création du contrat',
                'description' => "Facturés une seule fois, à l'ouverture du cabinet : mise en place du compte, "
                    .'paramétrage initial et signature du contrat de service.',
                'colonne_tarif' => 'frais_creation_contrat',
            ],
            [
                'cle' => 'frais_contrat_actif',
                'categorie' => 'ouverture',
                'libelle' => 'Frais de contrat actif',
                'description' => "Facturés à chaque (ré)activation d'un contrat de cours du cabinet "
                    .'(enseignant ↔ famille). Un contrat suspendu puis relancé déclenche un nouveau frais.',
                'colonne_tarif' => 'frais_contrat_actif',
            ],
            [
                'cle' => 'abonnement_mensuel_enseignant',
                'categorie' => 'abonnement',
                'libelle' => 'Abonnement mensuel — enseignant',
                'description' => "Facturé chaque mois pour CHAQUE enseignant actif du cabinet "
                    .'(période couverte). Le tarif est modulable : gratuit (0) ou suspendu (vide = non couvert).',
                'colonne_tarif' => 'abonnement_mensuel_enseignant',
            ],
            [
                'cle' => 'abonnement_mensuel_parent',
                'categorie' => 'abonnement',
                'libelle' => 'Abonnement mensuel — parent',
                'description' => "Facturé chaque mois pour CHAQUE parent actif. Laisser vide pour le rendre "
                    ."non facturable ; 0 le rend gratuit explicitement négocié.",
                'colonne_tarif' => 'abonnement_mensuel_parent',
            ],
            [
                'cle' => 'abonnement_mensuel_eleve',
                'categorie' => 'abonnement',
                'libelle' => 'Abonnement mensuel — élève',
                'description' => "Facturé chaque mois pour CHAQUE élève actif. Base du modèle commercial : "
                    .'des comptes élèves souscrits par les familles.',
                'colonne_tarif' => 'abonnement_mensuel_eleve',
            ],
            [
                'cle' => 'frais_rapport_mensuel',
                'categorie' => 'ponctuel',
                'libelle' => 'Frais — rapport mensuel validé',
                'description' => "Facturé à chaque rapport mensuel d'enseignant validé par l'admin du cabinet "
                    .'(documents pédagogiques de l\'échéance).',
                'colonne_tarif' => 'frais_rapport_mensuel',
            ],
            [
                'cle' => 'abonnement_annuel_admin',
                'categorie' => 'annuel',
                'libelle' => "Abonnement annuel — admin du cabinet",
                'description' => "Facturé une fois par an, à l'anniversaire de création du cabinet : "
                    ."la licence annuelle de l'écran admin.",
                'colonne_tarif' => 'abonnement_annuel_admin',
            ],
            [
                'cle' => 'maintenance_annuelle_site',
                'categorie' => 'annuel',
                'libelle' => 'Maintenance annuelle — site web',
                'description' => "Facturée à l'anniversaire du cabinet : hébergement, sauvegardes et petites "
                    ."corrections du site public.",
                'colonne_tarif' => 'maintenance_annuelle_site',
            ],
            [
                'cle' => 'taux_commission_librairie',
                'categorie' => 'commission',
                'libelle' => "Commission — librairie",
                'description' => "Pourcentage prélevé sur chaque vente livrée via la librairie "
                    .'(assiette = montant de la vente, hors frais de livraison). Le débit se calcule automatiquement.',
                'colonne_tarif' => 'taux_commission_librairie',
            ],
            [
                'cle' => 'taux_commission_autres',
                'categorie' => 'commission',
                'libelle' => "Commission — autres services (%)",
                'description' => 'Pourcentage négocié pour les autres prestations à la commission '
                    .'(réservé aux évolutions futures ; non appliqué automatiquement en v1).',
                'colonne_tarif' => 'taux_commission_autres',
            ],
        ];

        foreach ($services as $ordre => $service) {
            ServicePlateforme::updateOrCreate(
                ['cle' => $service['cle']],
                $service + ['ordre' => $ordre, 'actif' => true],
            );
        }

        $lignesTypes = [
            ['libelle' => 'Google Ads — campagne de promotion locale', 'sens' => 'debit', 'montant' => 25000,
                'description' => 'Publicité payée en direct par le super-admin au profit du cabinet (justificatif demandé).'],
            ['libelle' => 'Hébergement et maintenance ponctuelle du site', 'sens' => 'debit', 'montant' => 15000,
                'description' => 'Hébergement du site public, sauvegardes et petites corrections ponctuelles.'],
            ['libelle' => "Formation des utilisateurs (sur site)", 'sens' => 'debit', 'montant' => 50000,
                'description' => "Session de prise en main du tableau de bord pour l'admin du cabinet."],
            ['libelle' => 'Assistance technique à distance (à l\'heure)', 'sens' => 'debit', 'montant' => 10000,
                'description' => 'Support téléphonique/WhatsApp au-delà du standard inclus.'],
            ['libelle' => "Aménagements visuels du site (affiches / photos)", 'sens' => 'debit', 'montant' => 5000,
                'description' => 'Conception graphique ou retouche d\'images sur demande du cabinet.'],
            ['libelle' => 'Remise commerciale', 'sens' => 'credit', 'montant' => 5000,
                'description' => 'Réduction négociée, à déduire du montant de la facture.'],
        ];

        foreach ($lignesTypes as $ordre => $ligne) {
            LigneTypePlateforme::updateOrCreate(
                ['libelle' => $ligne['libelle']],
                $ligne + ['ordre' => $ordre, 'actif' => true],
            );
        }
    }
}
