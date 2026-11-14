<?php

namespace App\Policies;

use App\Models\User;

/**
 * Comptes : réservés à l'administrateur. Pas de suppression : on désactive
 * le compte d'un employé qui part, pour garder l'historique du journal.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer_comptes');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('gerer_comptes');
    }

    public function create(User $user): bool
    {
        return $user->can('gerer_comptes');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('gerer_comptes');
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
