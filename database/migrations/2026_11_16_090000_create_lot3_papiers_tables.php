<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3 : suivi des immatriculations, retraits des papiers, archivage des documents scannés.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un dossier d'immatriculation par moto vendue
        Schema::create('immatriculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->unique()->constrained('motos')->cascadeOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            $table->string('statut', 30)->default('dossier_en_cours')->index()
                ->comment('dossier_en_cours, provisoire_recu, recepisse_recu, carte_grise_recue');
            $table->string('prestataire')->nullable();
            $table->date('date_depot')->nullable();
            $table->date('date_provisoire')->nullable()->comment('Document provisoire : valable 60 jours');
            $table->date('date_recepisse')->nullable();
            $table->date('date_carte_grise')->nullable();
            $table->string('numero_carte_grise', 50)->nullable();
            $table->dateTime('client_prevenu_le')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Remise des papiers : qui est venu, quand, quel document
        Schema::create('retraits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immatriculation_id')->constrained('immatriculations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_retrait');
            $table->string('document', 20)->comment('provisoire, recepisse, carte_grise, plaque');
            $table->string('retire_par');
            $table->boolean('est_le_client')->default(true);
            $table->string('telephone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Documents scannés (PDF, photos), chiffrés sur le disque
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('titre');
            $table->string('chemin');
            $table->string('nom_original');
            $table->string('mime', 100);
            $table->unsignedInteger('taille')->comment('Octets, avant chiffrement');
            $table->boolean('chiffre')->default(false);
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('facture_fournisseur_id')->nullable()->constrained('factures_fournisseur')->nullOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Un document peut concerner plusieurs motos (facture d'un lot de 20 motos)
        Schema::create('document_moto', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->primary(['document_id', 'moto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_moto');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('retraits');
        Schema::dropIfExists('immatriculations');
    }
};
