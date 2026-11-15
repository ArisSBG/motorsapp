<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lot 4 : objectif de ventes par mois (ex. 20 motos), questionnaire 12.7. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objectifs_vente', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('mois');
            $table->unsignedSmallInteger('nombre_motos');
            $table->timestamps();

            $table->unique(['annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectifs_vente');
    }
};
