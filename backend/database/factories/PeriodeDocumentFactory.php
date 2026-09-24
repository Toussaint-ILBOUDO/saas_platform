<?php

namespace Database\Factories;

use App\Models\PeriodeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodeDocumentFactory extends Factory
{
    protected $model = PeriodeDocument::class;

    public function definition(): array
    {
        $nom = $this->faker->unique()->word();

        return [
            'nom' => $nom,
            'sigle' => strtoupper(substr($nom, 0, 5)),
        ];
    }
}
