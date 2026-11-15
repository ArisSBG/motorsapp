<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;

/**
 * Profil : chacun peut changer son nom et son mot de passe (avec le mot de passe actuel).
 * L'adresse e-mail sert d'identifiant : seul l'administrateur la modifie (écran Utilisateurs).
 */
class Profil extends EditProfile
{
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->disabled()
            ->dehydrated(false)
            ->helperText('Identifiant de connexion : à faire changer par l’administrateur.');
    }
}
