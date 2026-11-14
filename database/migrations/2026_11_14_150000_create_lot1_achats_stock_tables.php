<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 1 : fournisseurs, factures d'achat, versements, motos et frais.
 * Montants en FCFA entiers (pas de centimes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->string('piece_identite')->nullable()->comment('CNIB, RCCM ou IFU');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('factures_fournisseur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->string('numero', 60);
            $table->date('date_facture');
            $table->unsignedInteger('montant_transport')->default(0)->comment('Réparti à parts égales sur les motos');
            $table->unsignedInteger('remise')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['fournisseur_id', 'numero']);
        });

        Schema::create('motos', function (Blueprint $table) {
            $table->id();
            $table->string('chassis', 30)->unique();
            $table->string('numero_moteur', 40)->nullable();
            $table->string('marque', 40);
            $table->string('modele', 60);
            $table->string('couleur', 40)->nullable();
            $table->unsignedSmallInteger('cylindree')->nullable();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->foreignId('facture_fournisseur_id')->nullable()->constrained('factures_fournisseur')->nullOnDelete();
            $table->unsignedInteger('prix_achat');
            $table->date('date_reception');
            $table->string('plaque', 20)->nullable();
            $table->string('cmc', 40)->nullable();
            $table->string('statut', 30)->default('en_stock')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('frais_moto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->string('type', 20)->comment('transport, reparation, capots, autre');
            $table->unsignedInteger('montant');
            $table->string('description')->nullable();
            $table->date('date_frais');
            // Renseigné pour la part de transport calculée depuis une facture
            $table->foreignId('facture_fournisseur_id')->nullable()->constrained('factures_fournisseur')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('versements_fournisseur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_fournisseur_id')->constrained('factures_fournisseur')->cascadeOnDelete();
            $table->date('date_versement');
            $table->unsignedInteger('montant');
            $table->string('mode', 20)->comment('especes, virement, mobile_money, cheque');
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Un versement peut viser des motos précises de la facture (optionnel)
        Schema::create('moto_versement_fournisseur', function (Blueprint $table) {
            $table->foreignId('versement_fournisseur_id')->constrained('versements_fournisseur')->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->primary(['versement_fournisseur_id', 'moto_id']);
        });

        // Double vérification (application d'authentification) + codes de secours
        Schema::table('users', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']);
        });
        Schema::dropIfExists('moto_versement_fournisseur');
        Schema::dropIfExists('versements_fournisseur');
        Schema::dropIfExists('frais_moto');
        Schema::dropIfExists('motos');
        Schema::dropIfExists('factures_fournisseur');
        Schema::dropIfExists('fournisseurs');
    }
};
