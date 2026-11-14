<?php

namespace App\Filament\Papiers\Resources\Immatriculations;

use App\Enums\DocumentRemis;
use App\Enums\StatutImmatriculation;
use App\Models\Immatriculation;
use App\Support\Montant;
use App\Support\Telephone;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Actions rapides sur un dossier, utilisées dans la liste et sur la fiche.
 */
class ActionsPapiers
{
    private static function champDate(string $nom, string $libelle): DatePicker
    {
        return DatePicker::make($nom)->label($libelle)->default(now())->maxDate(now())->required()->native(false)->displayFormat('d/m/Y');
    }

    public static function provisoire(): Action
    {
        return Action::make('provisoire')
            ->label('Provisoire reçu')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('warning')
            ->visible(fn (Immatriculation $record) => $record->date_provisoire === null && auth()->user()->can('update', $record))
            ->schema([self::champDate('date_provisoire', 'Reçu le')])
            ->action(fn (Immatriculation $record, array $data) => $record->update($data))
            ->successNotificationTitle('Document provisoire enregistré (valable 60 jours)');
    }

    public static function recepisse(): Action
    {
        return Action::make('recepisse')
            ->label('Récépissé reçu')
            ->icon(Heroicon::OutlinedDocumentCheck)
            ->color('info')
            ->visible(fn (Immatriculation $record) => $record->date_recepisse === null && $record->date_carte_grise === null && auth()->user()->can('update', $record))
            ->schema([self::champDate('date_recepisse', 'Reçu le')])
            ->action(fn (Immatriculation $record, array $data) => $record->update($data))
            ->successNotificationTitle('Récépissé enregistré');
    }

    public static function carteGrise(): Action
    {
        return Action::make('carte_grise')
            ->label('Carte grise reçue')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (Immatriculation $record) => $record->date_carte_grise === null && auth()->user()->can('update', $record))
            ->schema([
                self::champDate('date_carte_grise', 'Reçue le'),
                TextInput::make('numero_carte_grise')->label('N° de carte grise')->maxLength(50),
                TextInput::make('plaque')->label('Numéro de plaque')->maxLength(20)->required(),
            ])
            ->action(function (Immatriculation $record, array $data) {
                $record->moto->update(['plaque' => $data['plaque']]);
                unset($data['plaque']);
                $record->update($data);
            })
            ->successNotificationTitle('Carte grise enregistrée : pensez à prévenir le client');
    }

    /** Ouvre WhatsApp avec un message prêt à envoyer, et note que le client a été prévenu. */
    public static function prevenir(): Action
    {
        return Action::make('prevenir')
            ->label('Prévenir le client')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->visible(fn (Immatriculation $record) => in_array($record->statut, [StatutImmatriculation::CarteGriseRecue, StatutImmatriculation::RecepisseRecu], true)
                && filled($record->vente?->client?->telephone) && ! $record->carteGriseRemise())
            ->modalDescription(fn (Immatriculation $record) => 'WhatsApp va s’ouvrir avec le message pour '.$record->vente->client->nom.' ('.$record->vente->client->telephone.').')
            ->modalSubmitActionLabel('Ouvrir WhatsApp')
            ->action(function (Immatriculation $record, $livewire) {
                $record->update(['client_prevenu_le' => now()]);
                $url = Telephone::lienWhatsApp($record->vente->client->telephone, self::message($record));
                $livewire->js('window.open('.json_encode($url).', "_blank")');
            });
    }

    public static function message(Immatriculation $record): string
    {
        $moto = $record->moto;
        $document = $record->statut === StatutImmatriculation::CarteGriseRecue ? 'votre carte grise' : 'votre récépissé';

        return "Bonjour {$record->vente->client->nom}, {$document} pour la moto {$moto->marque} {$moto->modele} "
            ."(châssis {$moto->chassis}) est disponible chez ".config('entreprise.nom').', '.config('entreprise.adresse')
            .'. Merci de passer la récupérer avec votre pièce d’identité.';
    }

    /**
     * Remise d'un papier. Si le client doit encore plus de 50.000 FCFA, la remise est refusée
     * (sauf décision de l'administrateur).
     */
    public static function retrait(): Action
    {
        return Action::make('retrait')
            ->label('Remettre les papiers')
            ->icon(Heroicon::OutlinedHandRaised)
            ->color('primary')
            ->visible(fn (Immatriculation $record) => auth()->user()->can('create', \App\Models\Retrait::class) && ! $record->carteGriseRemise())
            ->modalDescription(function (Immatriculation $record) {
                $reste = $record->vente?->reste() ?? 0;

                return $reste > 0 ? 'Attention : le client doit encore '.Montant::format($reste).'.' : 'Le client a tout payé.';
            })
            ->schema(fn (Immatriculation $record) => [
                Select::make('document')
                    ->label('Document remis')
                    ->options(DocumentRemis::class)
                    ->default($record->statut === StatutImmatriculation::CarteGriseRecue ? DocumentRemis::CarteGrise : DocumentRemis::Recepisse)
                    ->required(),
                self::champDate('date_retrait', 'Date'),
                Toggle::make('est_le_client')
                    ->label('C’est le client lui-même qui récupère')
                    ->default(true)
                    ->live(),
                TextInput::make('retire_par')
                    ->label('Nom de la personne qui récupère')
                    ->required(fn (Get $get) => ! $get('est_le_client'))
                    ->visible(fn (Get $get) => ! $get('est_le_client'))
                    ->maxLength(255),
                TextInput::make('telephone')
                    ->label('Téléphone de cette personne')
                    ->visible(fn (Get $get) => ! $get('est_le_client'))
                    ->maxLength(30),
                Toggle::make('forcer')
                    ->label('Remettre quand même (décision de l’administrateur)')
                    ->visible(fn () => auth()->user()->estAdmin() && ! ($record->vente?->peutRemettrePapiers() ?? true)),
                Textarea::make('notes')->rows(2),
            ])
            ->action(function (Immatriculation $record, array $data, Action $action) {
                $forcer = (bool) ($data['forcer'] ?? false) && auth()->user()->estAdmin();
                unset($data['forcer']);

                if ($record->vente && ! $record->vente->peutRemettrePapiers() && ! $forcer) {
                    Notification::make()
                        ->title('Papiers non remis')
                        ->body('Le client doit encore '.Montant::format($record->vente->reste())
                            .'. Les papiers ne sont remis que si le reste est au plus '.Montant::format(config('entreprise.seuil_remise_papiers')).'.')
                        ->danger()
                        ->persistent()
                        ->send();
                    $action->halt();
                }

                if ($data['est_le_client']) {
                    $data['retire_par'] = $record->vente?->client?->nom ?? 'Client';
                    $data['telephone'] = $record->vente?->client?->telephone;
                }

                $record->retraits()->create($data);
                Notification::make()->title('Remise enregistrée')->success()->send();
            });
    }
}
