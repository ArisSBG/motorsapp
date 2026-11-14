<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2 : clients, ventes (une ou plusieurs motos), paiements par tranches, échéancier.
 * Montants en FCFA entiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable()->comment('Quartier ou adresse');
            $table->string('type', 20)->default('particulier')->comment('particulier, entreprise, revendeur');
            $table->string('type_piece', 20)->nullable()->comment('cnib, passeport, permis, autre');
            $table->string('numero_piece', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('telephone');
        });

        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 40)->unique()->comment('BURKINA-MOTORS-2026-0001');
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Vendeur');
            $table->date('date_vente');
            $table->string('statut', 20)->default('en_cours')->index()->comment('reservation, en_cours, soldee, annulee');
            // Apporteur d'affaires (client qui envoie un client) : commission = coût de la vente
            $table->string('apporteur_nom')->nullable();
            $table->string('apporteur_telephone', 30)->nullable();
            $table->unsignedInteger('commission')->default(0);
            $table->date('soldee_le')->nullable()->index()->comment('Date du paiement qui a soldé la vente : le bénéfice compte ce jour-là');
            $table->text('motif_annulation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vente_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->restrictOnDelete();
            $table->unsignedInteger('prix_vente');
            // Coûts payés par le vendeur pour cette moto (questionnaire 7.2)
            $table->unsignedInteger('frais_immatriculation')->default(0);
            $table->unsignedInteger('cout_casque')->default(0);
            $table->timestamps();
        });

        Schema::create('paiements_client', function (Blueprint $table) {
            $table->id();
            $table->string('numero_recu', 30)->unique()->comment('REC-2026-0001');
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_paiement');
            $table->unsignedInteger('montant');
            $table->string('mode', 20);
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->date('date_prevue');
            $table->unsignedInteger('montant');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echeances');
        Schema::dropIfExists('paiements_client');
        Schema::dropIfExists('vente_lignes');
        Schema::dropIfExists('ventes');
        Schema::dropIfExists('clients');
    }
};
