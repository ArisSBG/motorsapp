<?php

namespace App\Filament\Papiers\Resources\Immatriculations\Schemas;

use App\Models\Immatriculation;
use App\Support\Montant;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImmatriculationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Moto et client')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        TextEntry::make('moto.chassis')->label('Châssis')->fontFamily('mono')->weight('bold')->copyable(),
                        TextEntry::make('moto_libelle')->label('Moto')->state(fn (Immatriculation $record) => trim("{$record->moto->marque} {$record->moto->modele} {$record->moto->couleur}")),
                        TextEntry::make('moto.cmc')->label('CMC')->placeholder('—'),
                        TextEntry::make('moto.plaque')->label('Plaque')->placeholder('—'),
                        TextEntry::make('vente.client.nom')->label('Client'),
                        TextEntry::make('vente.client.telephone')->label('Téléphone')->placeholder('—'),
                        TextEntry::make('vente.numero')->label('Facture')->placeholder('—'),
                        TextEntry::make('vente.date_vente')->label('Vendue le')->date('d/m/Y'),
                    ]),
                Section::make('Paiement du client')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('reste_du')
                            ->label('Doit encore')
                            ->state(fn (Immatriculation $record) => $record->vente?->reste() ?? 0)
                            ->formatStateUsing(fn ($state) => Montant::format($state))
                            ->weight('bold')
                            ->color(fn (Immatriculation $record) => ($record->vente?->peutRemettrePapiers() ?? true) ? 'success' : 'danger')
                            ->helperText(fn (Immatriculation $record) => ($record->vente?->peutRemettrePapiers() ?? true)
                                ? 'Les papiers peuvent être remis.'
                                : 'Papiers à ne pas remettre : reste supérieur à '.Montant::format(config('entreprise.seuil_remise_papiers')).'.'),
                    ]),
                Section::make('Dossier')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('statut')->badge(),
                        TextEntry::make('prestataire')->placeholder('—'),
                        TextEntry::make('date_depot')->label('Dépôt du dossier')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('date_provisoire')
                            ->label('Provisoire reçu')
                            ->date('d/m/Y')
                            ->placeholder('—')
                            ->helperText(fn (Immatriculation $record) => $record->provisoireExpireLe() ? 'Valable jusqu’au '.$record->provisoireExpireLe()->format('d/m/Y') : null),
                        TextEntry::make('date_recepisse')->label('Récépissé reçu')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('date_carte_grise')->label('Carte grise reçue')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('numero_carte_grise')->label('N° carte grise')->placeholder('—'),
                        TextEntry::make('client_prevenu_le')->label('Client prévenu le')->dateTime('d/m/Y à H:i')->placeholder('Pas encore'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
