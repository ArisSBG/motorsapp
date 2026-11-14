<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Page de connexion : après 5 essais ratés, blocage de 15 minutes
 * (cahier des charges, section 6.1) au lieu d'une minute par défaut.
 * On peut se connecter depuis n'importe quel espace : chacun est ensuite envoyé vers le sien.
 */
class Login extends BaseLogin
{
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        $method ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 2)[1]['function'];

        if ($method === 'authenticate') {
            $decaySeconds = 15 * 60;
        }

        return parent::rateLimit($maxAttempts, $decaySeconds, $method, $component);
    }

    /** Compte actif avec au moins un espace : la redirection se fait ensuite (LoginResponse). */
    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        return $user instanceof User && $user->actif && $user->panneaux()->isNotEmpty();
    }
}
