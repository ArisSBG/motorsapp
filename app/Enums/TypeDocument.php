<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/** Documents archivés (questionnaire 9.1). */
enum TypeDocument: string implements HasIcon, HasLabel
{
    case FactureFournisseur = 'facture_fournisseur';
    case FactureVente = 'facture_vente';
    case CarteGrise = 'carte_grise';
    case Recepisse = 'recepisse';
    case Provisoire = 'provisoire';
    case PieceIdentite = 'piece_identite';
    case Recu = 'recu';
    case Contrat = 'contrat';
    case Douane = 'douane';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::FactureFournisseur => 'Facture fournisseur',
            self::FactureVente => 'Facture de vente',
            self::CarteGrise => 'Carte grise',
            self::Recepisse => 'Récépissé',
            self::Provisoire => 'Immatriculation provisoire',
            self::PieceIdentite => 'Pièce d’identité',
            self::Recu => 'Reçu de paiement',
            self::Contrat => 'Contrat de vente',
            self::Douane => 'Document douanier',
            self::Autre => 'Autre',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::PieceIdentite => Heroicon::OutlinedIdentification,
            self::CarteGrise, self::Recepisse, self::Provisoire => Heroicon::OutlinedDocumentCheck,
            self::FactureFournisseur, self::FactureVente, self::Recu => Heroicon::OutlinedReceiptPercent,
            default => Heroicon::OutlinedDocument,
        };
    }

    /** Données personnelles : visibles seulement par les profils qui suivent les papiers. */
    public function estSensible(): bool
    {
        return $this === self::PieceIdentite;
    }
}
