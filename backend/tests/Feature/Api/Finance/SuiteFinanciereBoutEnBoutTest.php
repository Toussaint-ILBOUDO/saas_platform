<?php

namespace Tests\Feature\Api\Finance;

use App\Models\AffectationEnseignant;
use App\Models\BulletinPaie;
use App\Models\Classe;
use App\Models\ContratCours;
use App\Models\Eleve;
use App\Models\EnseignantProfil;
use App\Models\Facture;
use App\Models\Matiere;
use App\Models\PeriodeComptable;
use App\Models\TypeCours;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TenantTestCase;
use Tests\Traits\InteractsWithCabinets;

/**
 * Chaîne financière bout en bout (T7A.7 → T7A.8 → T7A.9).
 *
 * Une séance de cours est saisie au cahier de texte par l'enseignant (T7A.5),
 * son rapport mensuel en naît (heure issue de la séance, jamais saisie),
 * l'administration le VALIDE — le même rapport validé alimente alors la
 * facture parent (T7A.8) ET le bulletin de paie de l'enseignant (T7A.9) :
 * les deux montants de cours sont identiques par construction (même source),
 * et chacun ne voit que son périmètre.
 */
class SuiteFinanciereBoutEnBoutTest extends TenantTestCase
{
    use InteractsWithCabinets;

    private function connecte(string $slug, string $email): void
    {
        tenancy()->initialize($slug);
        User::where('email', $email)->update(['password' => Hash::make('Secret1234')]);
        tenancy()->end();

        $this->postJson("http://{$slug}.localhost/api/auth/connexion", [
            'email' => $email,
            'password' => 'Secret1234',
        ])->assertOk();
    }

    private function api(string $slug, string $chemin): string
    {
        return "http://{$slug}.localhost/api/{$chemin}";
    }

    private function creeUtilisateur(string $slug, string $nom, string $prenom, string $email): User
    {
        return User::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => Hash::make('Secret1234'),
        ]);
    }

    /**
     * Cabinet minimal : période ouverte, un élève, un enseignant (2 500 F/h).
     *
     * @return array<string, mixed>
     */
    private function socle(string $slug): array
    {
        $this->makeCabinet($slug);

        tenancy()->initialize($slug);

        $periode = PeriodeComptable::create([
            'label' => 'Octobre 2026',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'type' => 'mensuel',
        ]);

        $eleveUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Ibrahim', "ibrahim@{$slug}.local");
        $eleveUser->assignRole('eleve');
        $mereUser = $this->creeUtilisateur($slug, 'Ouédraogo', 'Mère', "mere@{$slug}.local");
        $mereUser->assignRole('parent');
        $mereUser->parentProfil()->firstOrCreate([], []);

        $classe = Classe::create(['nom' => 'Terminale', 'sigle' => 'Tle']);
        $eleve = Eleve::create([
            'user_id' => $eleveUser->id,
            'parent_id' => $mereUser->id,
            'classe_id' => $classe->id,
            'statut' => true,
        ]);

        $matiere = Matiere::create(['nom' => 'Mathématiques', 'sigle' => 'MATH']);

        $profUser = $this->creeUtilisateur($slug, 'Kaboré', 'Aïcha', "prof@{$slug}.local");
        $profUser->assignRole('enseignant');
        $prof = EnseignantProfil::create(['user_id' => $profUser->id]);
        $prof->matieres()->sync([$matiere->id]);

        $typeCours = TypeCours::create([
            'code' => 'DOM',
            'libelle' => 'À domicile',
            'actif' => true,
        ]);

        $contrat = ContratCours::create([
            'eleve_id' => $eleve->id,
            'type_cours_id' => $typeCours->id,
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'autres_frais_suivi' => 0,
            'statut' => 'actif',
        ]);

        $affectation = AffectationEnseignant::create([
            'contrat_cours_id' => $contrat->id,
            'enseignant_id' => $prof->id,
            'matiere_id' => $matiere->id,
            'taux_horaire_enseignant' => 2500,
            'nombre_heures_prevues' => 4,
            'date_affectation' => '2026-10-01',
            'statut' => 'actif',
        ]);

        $resultat = [
            'periode_id' => (int) $periode->id,
            'affectation_id' => (int) $affectation->id,
            'contrat_id' => (int) $contrat->id,
            'matiere_id' => (int) $matiere->id,
            'prof_id' => (int) $prof->id,
            'enseignant_email' => "prof@{$slug}.local",
            'admin_email' => "admin@{$slug}.local",
            'mere_email' => "mere@{$slug}.local",
        ];

        tenancy()->end();

        return $resultat;
    }

    public function test_une_seance_validee_facture_et_paie_le_meme_rapport(): void
    {
        $slug = 'c1';
        $socle = $this->socle($slug);

        // 1. L'enseignant saisit une séance de 2 h au cahier de texte (09h-11h).
        $this->connecte($slug, $socle['enseignant_email']);
        $this->postJson($this->api($slug, 'enseignant/cahiers-textes'), [
            'affectation_enseignant_id' => $socle['affectation_id'],
            'date_seance' => '2026-10-02',
            'heure_debut' => '09:00',
            'heure_fin' => '11:00',
            'contenu_cours' => 'Cours sur les dérivées et leurs applications.',
        ])->assertCreated();

        // 2. Le rapport mensuel en naît : l'heure vient de la séance, pas du client.
        $rapport = $this->postJson($this->api($slug, 'pedagogie/rapports-mensuels'), [
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
            'observations' => 'Bonne progression sur le chapitre.',
        ])->assertCreated()
            ->assertJsonPath('data.volume_horaire_cumule', 2)
            ->json('data');

        // 3. L'administration valide (D-051) : c'est la clef qui débloque tout.
        $this->connecte($slug, $socle['admin_email']);
        $this->postJson(
            $this->api($slug, "admin/pedagogie/rapports-mensuels/{$rapport['id']}/valider")
        )->assertOk()
            ->assertJsonPath('data.statut', 'valide');
        $this->assertSame('valide', RapportMensuelEtat::rapport($socle['periode_id'], $socle['contrat_id']));

        // 4. La facture parent consomme le RAPPORT VALIDÉ : 2 h × 2 500 F.
        $facture = $this->postJson($this->api($slug, 'admin/factures'), [
            'contrat_cours_id' => $socle['contrat_id'],
            'periode_id' => $socle['periode_id'],
            'frais_suivi' => 1000,
            'autres_frais' => 500,
            'remise' => 0,
            'date_limite_paiement' => '2026-11-15',
            'commentaire' => 'Premier mois.',
        ])->assertCreated()
            ->assertJsonPath('data.volume_horaire_total', 2)
            // Montant des cours = somme des lignes (2 h × 2 500 F), porté par
            // `montant_total` avec les frais — le cours n'est pas un champ
            // direct de la facture, il se relit sur les lignes du rapport.
            ->assertJsonPath('data.montant_total', 6500)
            ->json('data');
        $coursFacture = (int) collect($facture['lignes'])->sum('montant');

        // 5. Le bulletin de paie consomme le MÊME rapport validé : brut identique.
        $bulletin = $this->postJson($this->api($slug, 'admin/bulletins-paie'), [
            'periode_id' => $socle['periode_id'],
            'frais_suivi' => [$socle['prof_id'] => 500],
        ])->assertCreated()
            ->assertJsonPath('data.0.total_heures', 2)
            ->assertJsonPath('data.0.montant_brut', 5000)
            ->assertJsonPath('data.0.frais_suivi', 500)
            ->assertJsonPath('data.0.montant_net', 4500)
            ->json('data.0');

        // Cohérence : la facture et le bulletin repartent des mêmes heures validées.
        $this->assertSame((float) $facture['volume_horaire_total'], (float) $bulletin['total_heures']);
        $this->assertSame($coursFacture, $bulletin['montant_brut']);
        $this->assertSame(1, Facture::count());
        $this->assertSame(1, BulletinPaie::count());

        // 6. Périmètres : le parent voit la facture, l'enseignant voit le bulletin.
        $this->connecte($slug, $socle['mere_email']);
        $this->getJson($this->api($slug, 'mes-factures'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero_facture', $facture['numero_facture']);

        $this->connecte($slug, $socle['enseignant_email']);
        $this->getJson($this->api($slug, 'mes-bulletins'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero', $bulletin['numero']);
    }
}

/**
 * Accès direct à l'état du rapport pour l'assertion de source de vérité (le
 * rapport validé est consommé par la facture ET le bulletin sans être relu).
 */
final class RapportMensuelEtat
{
    public static function rapport(int $periodeId, int $contratId): string
    {
        return (string) \App\Models\RapportMensuelEnseignant::query()
            ->where('periode_id', $periodeId)
            ->where('contrat_cours_id', $contratId)
            ->value('statut');
    }
}