<?php

namespace App\Observers;

use App\Models\User;
use App\Services\SessionsUtilisateur;

/**
 * Compte désactivé ou mot de passe changé par l'administrateur :
 * l'utilisateur est déconnecté de tous ses appareils immédiatement.
 */
class UserObserver
{
    public function __construct(private SessionsUtilisateur $sessions) {}

    public function updated(User $user): void
    {
        $desactive = $user->wasChanged('actif') && ! $user->actif;
        $motDePasseChangeParAutrui = $user->wasChanged('password') && ! $user->is(auth()->user());

        if ($desactive || $motDePasseChangeParAutrui) {
            $this->sessions->deconnecterPartout($user);
        }
    }
}
