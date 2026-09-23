<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_commissions', function (Blueprint $table) {
            $table->id();

            $table->string('nom_du_type', 50);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_commissions');
    }
};