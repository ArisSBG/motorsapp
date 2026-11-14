<?php

namespace App\Filament\Gestion\Resources\Ventes\Schemas;

use App\Filament\Gestion\Resources\Clients\ClientResource;
use App\Filament\Gestion\Resources\Ventes\VenteResource;
use App\Models\Vente;
use App\Models\VenteLigne;
use App\Support\Montant;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VenteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $fcfa = fn ($state) => Montant::format($state);
        $benef = fn () => VenteResource::peutVoirBenefice();

        return $schema
            ->columns(3)
            ->components([
                Section::make('Vente')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('numero')->label('N° de facture')->weight('bold')->copyable(),
                        TextEntry::make('date_vente')->label('Date')->date('d/m/Y'),
                        TextEntry::make('statut')->badge(),
                        TextEntry::make('client.nom')
                            ->label('Client')
                            ->url(fn (Vente $record) => ClientResource::getUrl('view', ['record' => $record->client_id])),
                        TextEntry::make('client.telephone')->label('Téléphone'),
                        TextEntry::make('vendeur.name')->label('Vendeur')->placeholder('—'),
                        TextEntry::make('apporteur_nom')
                            ->label('Apporteur')
                            ->placeholder('—')
                            ->suffix(fn (Vente $record) => $record->commission ? ' — commission '.Montant::format($record->commission) : null),
                        TextEntry::make('motif_annulation')->label('Motif d’annulation')->visible(fn (Vente $record) => filled($record->motif_annulation))->columnSpan(2),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),

                Section::make('Paiement')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('total')
                            ->label('Total de la vente')
                            ->state(fn (Vente $record) => $record->total())
                            ->formatStateUsing($fcfa),
                        TextEntry::make('paye')
                            ->label('Déjà payé')
                            ->state(fn (Vente $record) => $record->paye())
                            ->formatStateUsing($fcfa),
                        TextEntry::make('reste')
                            ->label('Reste à payer')
                            ->state(fn (Vente $record) => $record->reste())
                            ->formatStateUsing($fcfa)
                            ->weight('bold')
                            ->size('lg')
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                            ->helperText(fn (Vente $record) => $record->reste() > 0 && $record->peutRemettrePapiers()
                                ? 'Reste ≤ '.Montant::format(config('entreprise.seuil_remise_papiers')).' : les papiers peuvent être remis.'
                                : null),
                        TextEntry::make('benefice')
                            ->label('Bénéfice')
                            ->state(fn (Vente $record) => $record->benefice())
                            ->formatStateUsing($fcfa)
                            ->weight('bold')
                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                            ->helperText(fn (Vente $record) => $record->soldee_le
                                ? 'Compté le '.$record->soldee_le->format('d/m/Y').' (vente soldée)'
                                : 'Sera compté quand la vente sera entièrement payée')
                            ->visible($benef),
                    ]),

                Section::make('Motos')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('lignes')
                            ->hiddenLabel()
                            ->table(self::colonnesLignes())
                            ->schema(self::champsLignes()),
                    ]),
            ]);
    }

    /** Colonnes du tableau des motos ; « Bénéfice » seulement pour l'administrateur. */
    private static function colonnesLignes(): array
    {
        $colonnes = [
            RepeatableEntry\TableColumn::make('Châssis'),
            RepeatableEntry\TableColumn::make('Moto'),
            RepeatableEntry\TableColumn::make('Prix de vente')->alignEnd(),
            RepeatableEntry\TableColumn::make('Immatriculation')->alignEnd(),
            RepeatableEntry\TableColumn::make('Casque')->alignEnd(),
        ];
        if (VenteResource::peutVoirBenefice()) {
            $colonnes[] = RepeatableEntry\TableColumn::make('Bénéfice')->alignEnd();
        }

        return $colonnes;
    }

    private static function champsLignes(): array
    {
        $fcfa = fn ($state) => Montant::format($state);
        $champs = [
            TextEntry::make('moto.chassis')->fontFamily('mono')->weight('bold'),
            TextEntry::make('moto_libelle')->state(fn (VenteLigne $record) => trim("{$record->moto->marque} {$record->moto->modele} {$record->moto->couleur}")),
            TextEntry::make('prix_vente')->formatStateUsing($fcfa)->alignEnd(),
            TextEntry::make('frais_immatriculation')->formatStateUsing($fcfa)->alignEnd(),
            TextEntry::make('cout_casque')->formatStateUsing($fcfa)->alignEnd(),
        ];
        if (VenteResource::peutVoirBenefice()) {
            $champs[] = TextEntry::make('benefice_ligne')
                ->state(fn (VenteLigne $record) => $record->benefice())
                ->formatStateUsing($fcfa)
                ->alignEnd();
        }

        return $champs;
    }
}
