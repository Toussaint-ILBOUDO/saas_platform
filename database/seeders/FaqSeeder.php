<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('faq_sections')->exists()) {
            return;
        }

        $now = now();

        $sections = [
            [
                'title' => 'Inscription et scolarité',
                'slug' => Str::slug('Inscription et scolarité'),
                'description' => 'Tout savoir sur l\'inscription de votre enfant au Cabinet K\'Educ.',
                'order_index' => 0,
                'questions' => [
                    [
                        'question' => 'Comment inscrire mon enfant au Cabinet K\'Educ ?',
                        'answer' => 'L\'inscription se fait en remplissant le formulaire de demande de cours sur notre site ou en nous contactant directement par téléphone au +226 70 78 11 61 ou via WhatsApp. Après évaluation du niveau de l\'enfant, nous proposons un enseignant qualifié adapté à ses besoins.',
                    ],
                    [
                        'question' => 'Dès quel âge peut-on suivre des cours au Cabinet K\'Educ ?',
                        'answer' => 'Nous accueillons les élèves du primaire jusqu\'au supérieur, ainsi que les adultes en formation continue. Chaque apprenant bénéficie d\'un accompagnement personnalisé selon son niveau et ses objectifs.',
                    ],
                    [
                        'question' => 'Les cours se déroulent-ils à domicile ou en ligne ?',
                        'answer' => 'Les deux formules sont proposées. Nous offrons des cours d\'appui à domicile à Ouagadougou et en ligne pour toute la province et à l\'international, grâce à une plateforme adaptée.',
                    ],
                ],
            ],
            [
                'title' => 'Cours et enseignants',
                'slug' => Str::slug('Cours et enseignants'),
                'description' => 'Le déroulement des cours et le profil de nos enseignants.',
                'order_index' => 1,
                'questions' => [
                    [
                        'question' => 'Qui sont les enseignants du Cabinet K\'Educ ?',
                        'answer' => 'Nos enseignants sont des professeurs qualifiés et expérimentés, sélectionnés pour leur pédagogie et leur maîtrise des programmes scolaires en vigueur. Chacun est suivi et évalué régulièrement par notre équipe.',
                    ],
                    [
                        'question' => 'Combien de séances de cours sont nécessaires par semaine ?',
                        'answer' => 'Le rythme recommandé varie selon les besoins de l\'élève : généralement 2 à 3 séances par semaine, avec un suivi des devoirs et des évaluations. Un programme personnalisé est défini dès la première séance.',
                    ],
                    [
                        'question' => 'Comment se déroule le suivi de la progression de mon enfant ?',
                        'answer' => 'L\'enseignant élabore un cahier de texte et un rapport mensuel. Les parents sont informés régulièrement de l\'évolution de leur enfant et peuvent demander des entretiens à tout moment.',
                    ],
                ],
            ],
            [
                'title' => 'Tarifs et paiement',
                'slug' => Str::slug('Tarifs et paiement'),
                'description' => 'Les modalités de paiement de nos prestations.',
                'order_index' => 2,
                'questions' => [
                    [
                        'question' => 'Quels sont les moyens de paiement acceptés ?',
                        'answer' => 'Les paiements peuvent être effectués par Orange Money au +226 55 37 19 47, par Moov Money au +226 72 41 78 22, ou en espèces au cabinet. Des facilités de paiement sont possibles selon les formules.',
                    ],
                    [
                        'question' => 'Peut-on payer par tranches ?',
                        'answer' => 'Oui, des facilités de paiement sont proposées. Les modalités sont définies lors de la signature du contrat en fonction du nombre de séances souscrites.',
                    ],
                ],
            ],
            [
                'title' => 'Bibliothèque et librairie',
                'slug' => Str::slug('Bibliothèque et librairie'),
                'description' => 'Nos services numériques complémentaires.',
                'order_index' => 3,
                'questions' => [
                    [
                        'question' => 'Qu\'est-ce que la bibliothèque numérique K\'Educ ?',
                        'answer' => 'La bibliothèque numérique met à disposition des documents pédagogiques (cours, exercices, évaluations) classés par niveau et par matière. Les membres peuvent télécharger, noter et commenter les ressources, et y déposer leurs propres documents.',
                    ],
                    [
                        'question' => 'Comment commander des fournitures scolaires en ligne ?',
                        'answer' => 'Rendez-vous dans l\'espace Librairie de notre site, ajoutez les articles souhaités au panier puis validez votre commande. Vous recevrez une confirmation avec le montant total et les modalités de livraison.',
                    ],
                ],
            ],
        ];

        foreach ($sections as $sectionData) {
            $questions = $sectionData['questions'];
            unset($sectionData['questions']);

            $sectionData['is_active'] = true;
            $sectionData['created_at'] = $now;
            $sectionData['updated_at'] = $now;

            $sectionId = DB::table('faq_sections')->insertGetId($sectionData);

            foreach ($questions as $index => $question) {
                DB::table('faq_questions')->insert([
                    'faq_section_id' => $sectionId,
                    'question' => $question['question'],
                    'answer' => $question['answer'],
                    'order_index' => $index,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
