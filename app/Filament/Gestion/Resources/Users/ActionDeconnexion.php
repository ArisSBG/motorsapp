<?php

namespace App\Filament\Gestion\Resources\Users;

use App\Models\User;
use App\Services\SessionsUtilisateur;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Voir les appareils connectés d'un utilisateur et les déconnecter tous
 * (téléphone perdu ou volé, employé qui part).
 */
class ActionDeconnexion
{
    public static function make(): Action
    {
        return Action::make('deconnecter')
            ->label('Appareils connectés')
            ->icon(Heroicon::OutlinedDevicePhoneMobile)
            ->color('danger')
            ->modalHeading(fn (User $record) => 'Appareils connectés — '.$record->name)
            ->modalDescription(fn (User $record) => self::description($record))
            ->modalSubmitActionLabel('Déconnecter tous ses appareils')
            ->requiresConfirmation()
            ->action(function (User $record) {
                $n = app(SessionsUtilisateur::class)->deconnecterPartout($record);
                Notification::make()
                    ->title($n ? "{$n} appareil(s) déconnecté(s)" : 'Aucun appareil connecté')
                    ->body('La personne devra se reconnecter avec son mot de passe.')
                    ->success()
                    ->send();
            });
    }

    private static function description(User $record): HtmlString
    {
        $sessions = app(SessionsUtilisateur::class)->liste($record);
        if ($sessions === []) {
            return new HtmlString('Aucun appareil connecté pour le moment.');
        }

        $lignes = collect($sessions)->map(fn (array $s) => '<li>'.e($s['appareil']).' — '.e($s['ip'] ?? '?')
            .' — dernière activité '.e($s['derniere_activite']->locale('fr')->diffForHumans()).'</li>')->join('');

        return new HtmlString('<ul style="text-align:left; list-style:disc; padding-left:1.25rem;">'.$lignes.'</ul>'
            .'<p style="margin-top:.5rem;">En cas de téléphone perdu ou volé, déconnectez tout puis changez le mot de passe.</p>');
    }
}
