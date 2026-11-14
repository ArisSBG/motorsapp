<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as Contrat;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Après la connexion : vers l'espace demandé si le profil y a accès,
 * sinon vers son propre espace (une secrétaire connectée via « Gestion » arrive dans « Papiers »).
 */
class LoginResponse implements Contrat
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var User $user */
        $user = $request->user();
        $panneaux = $user->panneaux();

        if ($panneaux->contains(Filament::getCurrentOrDefaultPanel()->getId())) {
            return redirect()->intended(Filament::getUrl());
        }

        return redirect('/'.$panneaux->first());
    }
}
