<?php

namespace App\Filament\Gestion\Resources\Ventes\Pages;

use App\Enums\StatutVente;
use App\Filament\Gestion\Resources\Ventes\VenteResource;
use App\Models\Vente;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\On;

class ViewVente extends ViewRecord
{
    protected static string $resource = VenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('facture')
                ->label('Imprimer la facture')
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (Vente $record) => route('documents.facture-vente', $record))
                ->openUrlInNewTab()
                ->hidden(fn (Vente $record) => $record->statut === StatutVente::Annulee),

            Action::make('confirmer')
                ->label('Confirmer la vente')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Vente $record) => $record->statut === StatutVente::Reservation && auth()->user()->can('enregistrer_vente'))
                ->requiresConfirmation()
                ->modalDescription('La réservation devient une vente : la moto passe « Vendue ».')
                ->action(function (Vente $record) {
                    $record->update(['statut' => StatutVente::EnCours]);
                    app(\App\Services\SuiviVente::class)->recalculer($record->refresh());
                    Notification::make()->title('Vente confirmée')->success()->send();
                }),

            Action::make('annuler')
                ->label('Annuler la vente')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (Vente $record) => $record->estModifiable() && auth()->user()->can('update', $record))
                ->requiresConfirmation()
                ->modalDescription('Les motos reviennent en stock. Les paiements déjà reçus restent enregistrés (remboursement à noter).')
                ->schema([
                    Textarea::make('motif_annulation')
                        ->label('Motif (retour, échange contre une autre moto…)')
                        ->required(),
                ])
                ->action(function (Vente $record, array $data) {
                    $record->update(['statut' => StatutVente::Annulee, 'motif_annulation' => $data['motif_annulation']]);
                    Notification::make()->title('Vente annulée, motos remises en stock')->success()->send();
                }),

            EditAction::make()->visible(fn (Vente $record) => $record->estModifiable()),
        ];
    }

    #[On('totaux-modifies')]
    public function rafraichirTotaux(): void
    {
        $this->record->refresh();
    }
}
