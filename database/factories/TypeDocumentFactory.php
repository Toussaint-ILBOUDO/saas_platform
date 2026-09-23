<?php

namespace Database\Factories;

use App\Models\TypeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeDocumentFactory extends Factory
{
    protected $model = TypeDocument::class;

    public function definition(): array
    {
        $nom = $this->faker->unique()->word();

        return [
            'nom' => $nom,
            'sigle' => strtoupper(substr($nom, 0, 5)),
        ];
    }
}
