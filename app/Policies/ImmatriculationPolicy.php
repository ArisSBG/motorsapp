<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Dossiers d'immatriculation : suivis par la secrétaire ; suppression réservée à l'administrateur. */
class ImmatriculationPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'suivre_papiers';

    protected string $droitEcriture = 'suivre_papiers';

    public function delete(User $user, Model $model): bool
    {
        return $user->estAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->estAdmin();
    }
}
