<?php

namespace App\Modules\Users\Services;

use App\Models\Matiere;
use App\Models\User;
use App\Modules\Audit\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Import CSV d'enseignants (M1) — création en masse `User` + `EnseignantProfil`
 * + rôle, rapport ligne par ligne.
 *
 * Règles :
 *  - chaque ligne est importée dans sa propre transaction : une ligne en
 *    erreur n'annule pas les autres ;
 *  - colonnes reconnues (en-tête, ordre libre, casse/accents indifférents) :
 *    nom*, prenom*, email, telephone_whatsapp, telephone_appel, diplome_max,
 *    lieu_de_service, domicile, numero_orange_money, frais_annuel_regle,
 *    password, matieres (noms séparés par « | ») ;
 *  - mot de passe : colonne `password` de la ligne, sinon `password_defaut`
 *    de la requête — jamais renvoyé dans le rapport ;
 *  - une matière du CSV introuvable donne un AVERTISSEMENT, la ligne est
 *    quand même importée (ses autres champs sont valides).
 */
class ImportEnseignantCsvService
{
    private const COLONNES_REQUISES = ['nom', 'prenom'];

    private const COLONNES_CONNUES = [
        'nom', 'prenom', 'email', 'telephone_whatsapp', 'telephone_appel',
        'diplome_max', 'lieu_de_service', 'domicile', 'numero_orange_money',
        'frais_annuel_regle', 'password', 'matieres',
    ];

    private const MAX_LIGNES = 1000;

    /** @var array<string, string> */
    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    /**
     * @return array{
     *     importees: int,
     *     ignorees: int,
     *     erreurs: array<int, array{ligne: int, message: string, nom?: string, prenom?: string}>,
     *     avertissements: array<int, array{ligne: int, message: string}>
     * }
     */
    public function importer(UploadedFile $fichier, string $passwordDefaut): array
    {
        $lignes = $this->lignes($fichier);
        $entete = $lignes[0] ?? [];

        $colonnes = $this->mapColonnes($entete);
        $manquantes = array_diff(self::COLONNES_REQUISES, array_keys($colonnes));
        if ($manquantes !== []) {
            throw ValidationException::withMessages([
                'fichier' => 'En-tête CSV incomplet : colonne(s) manquante(s) — '
                    . implode(', ', $manquantes) . '. Attendu : nom, prenom (puis, en option, email, '
                    . 'telephone_whatsapp, telephone_appel, diplome_max, lieu_de_service, domicile, '
                    . 'numero_orange_money, frais_annuel_regle, password, matieres).',
            ]);
        }

        $donnees = array_slice($lignes, 1);
        if (count($donnees) > self::MAX_LIGNES) {
            throw ValidationException::withMessages([
                'fichier' => 'Fichier trop volumineux : ' . count($donnees) . ' lignes (maximum '
                    . self::MAX_LIGNES . ').',
            ]);
        }

        $rapport = [
            'importees' => 0,
            'ignorees' => 0,
            'erreurs' => [],
            'avertissements' => [],
        ];

        $emailsVus = [];
        $matieresParNom = $this->matieresParNom();

        foreach ($donnees as $index => $ligneBrute) {
            // +2 : 1-indexé, l'en-tête est la ligne 1.
            $numero = $index + 2;

            if ($this->ligneVide($ligneBrute)) {
                continue;
            }

            $valeurs = $this->valeurs($ligneBrute, $colonnes);
            $entreeErreur = [
                'ligne' => $numero,
                'nom' => $valeurs['nom'] ?? '',
                'prenom' => $valeurs['prenom'] ?? '',
            ];

            if ($valeurs['nom'] === '' || $valeurs['prenom'] === '') {
                $rapport['ignorees']++;
                $rapport['erreurs'][] = $entreeErreur + ['message' => 'Le nom et le prénom sont obligatoires.'];
                continue;
            }

            $email = $valeurs['email'] === '' ? null : $valeurs['email'];

            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rapport['ignorees']++;
                $rapport['erreurs'][] = $entreeErreur + ['message' => "Adresse e-mail invalide : {$email}."];
                continue;
            }

            if ($email !== null) {
                $cle = mb_strtolower($email);
                if (isset($emailsVus[$cle])) {
                    $rapport['ignorees']++;
                    $rapport['erreurs'][] = $entreeErreur + [
                        'message' => "Adresse e-mail déjà présente dans ce fichier (ligne {$emailsVus[$cle]}).",
                    ];
                    continue;
                }
                $emailsVus[$cle] = $numero;

                if (User::where('email', $email)->exists()) {
                    $rapport['ignorees']++;
                    $rapport['erreurs'][] = $entreeErreur + [
                        'message' => "Un compte existe déjà avec l'adresse {$email}.",
                    ];
                    continue;
                }
            }

            $password = $valeurs['password'] !== '' ? $valeurs['password'] : $passwordDefaut;
            if (mb_strlen($password) < 6) {
                $rapport['ignorees']++;
                $rapport['erreurs'][] = $entreeErreur + ['message' => 'Le mot de passe (colonne password) doit faire au moins 6 caractères.'];
                continue;
            }

            // Matières : noms séparés par « | » — inconnue = avertissement,
            // la ligne est importée avec les matières trouvées.
            $matiereIds = [];
            if ($valeurs['matieres'] !== '') {
                foreach (preg_split('/[|;,]/', $valeurs['matieres']) ?: [] as $nomMatiere) {
                    $nomMatiere = trim($nomMatiere);
                    if ($nomMatiere === '') {
                        continue;
                    }
                    $id = $matieresParNom[Str::lower($nomMatiere)] ?? null;
                    if ($id === null) {
                        $rapport['avertissements'][] = [
                            'ligne' => $numero,
                            'message' => "Matière introuvable, ignorée : {$nomMatiere}.",
                        ];
                    } else {
                        $matiereIds[] = $id;
                    }
                }
            }

            try {
                app(EnseignantService::class)->create([
                    'nom' => $valeurs['nom'],
                    'prenom' => $valeurs['prenom'],
                    'email' => $email,
                    'telephone_whatsapp' => $valeurs['telephone_whatsapp'] ?: null,
                    'telephone_appel' => $valeurs['telephone_appel'] ?: null,
                    'password' => $password,
                    'diplome_max' => $valeurs['diplome_max'] ?: null,
                    'lieu_de_service' => $valeurs['lieu_de_service'] ?: null,
                    'domicile' => $valeurs['domicile'] ?: null,
                    'numero_orange_money' => $valeurs['numero_orange_money'] ?: null,
                    'frais_annuel_regle' => $this->booleen($valeurs['frais_annuel_regle']),
                    'matieres' => array_values(array_unique($matiereIds)),
                ]);
                $rapport['importees']++;
            } catch (\Throwable $e) {
                $rapport['ignorees']++;
                $rapport['erreurs'][] = $entreeErreur + [
                    'message' => 'Création impossible : ' . Str::limit($e->getMessage(), 180, '…'),
                ];
            }
        }

        AuditService::enregistrer(
            'enseignant.importe',
            'Import CSV : ' . $rapport['importees'] . ' enseignant(s) importé(s), '
                . $rapport['ignorees'] . ' ligne(s) ignorée(s)',
            null,
            [
                'importees' => $rapport['importees'],
                'ignorees' => $rapport['ignorees'],
            ],
        );

        return $rapport;
    }

    /** @return array<int, array<int, string>> lignes déjà découpées (en-tête incluse) */
    private function lignes(UploadedFile $fichier): array
    {
        $contenu = (string) file_get_contents($fichier->getRealPath());

        // BOM UTF-8 (Excel) : sinon la première colonne « \xEF\xBB\xBF nom ».
        if (str_starts_with($contenu, "\xEF\xBB\xBF")) {
            $contenu = substr($contenu, 3);
        }

        $tableau = preg_split('/\r\n|\r|\n/', $contenu) ?: [];
        $tableau = array_values(array_filter($tableau, fn ($l) => trim($l) !== ''));

        $separateur = $this->detecterSeparateur($tableau[0] ?? '');

        return array_map(
            fn ($ligne) => array_map('trim', str_getcsv($ligne, $separateur, '"', '\\') ?: []),
            $tableau
        );
    }

    private function detecterSeparateur(string $premiereLigne): string
    {
        $candidats = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach (array_keys($candidats) as $sep) {
            $candidats[$sep] = substr_count($premiereLigne, $sep);
        }
        arsort($candidats);

        return array_key_first($candidats) ?: ';';
    }

    /**
     * En-tête → index de colonne, clés normalisées (minuscules, sans accent).
     *
     * @return array<string, int>
     */
    private function mapColonnes(array $entete): array
    {
        $map = [];
        foreach ($entete as $index => $colonne) {
            $cle = $this->normaliserCle($colonne);
            if (in_array($cle, self::COLONNES_CONNUES, true) && ! isset($map[$cle])) {
                $map[$cle] = $index;
            }
        }

        return $map;
    }

    private function normaliserCle(string $valeur): string
    {
        $valeur = Str::lower(trim($valeur));
        $valeur = strtr($valeur, self::ACCENTS);

        return str_replace([' ', '-'], '_', $valeur);
    }

    /**
     * @param  array<string, int>  $colonnes
     * @return array<string, string> toutes les colonnes connues (vides si absentes)
     */
    private function valeurs(array $ligne, array $colonnes): array
    {
        $sortie = array_fill_keys(self::COLONNES_CONNUES, '');

        foreach ($colonnes as $cle => $index) {
            $sortie[$cle] = trim((string) ($ligne[$index] ?? ''));
        }

        return $sortie;
    }

    /** @return array<string, int> nom en minuscules => id matière */
    private function matieresParNom(): array
    {
        return Matiere::query()
            ->pluck('id', 'nom')
            ->mapWithKeys(fn ($id, $nom) => [Str::lower($nom) => $id])
            ->all();
    }

    private function booleen(string $valeur): bool
    {
        return in_array(
            Str::lower($valeur),
            ['1', 'true', 'vrai', 'oui', 'o', 'y', 'yes'],
            true
        );
    }

    private function ligneVide(array $ligne): bool
    {
        foreach ($ligne as $cellule) {
            if (trim((string) $cellule) !== '') {
                return false;
            }
        }

        return true;
    }
}
