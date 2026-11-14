<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Documents : consultés et ajoutés par la secrétaire et le vendeur ; supprimés par l'administrateur. */
class DocumentPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'suivre_papiers';

    protected string $droitEcriture = 'ajouter_document';

    protected ?string $droitModification = 'supprimer_document';

    public function update(User $user, Model $model): bool
    {
        return $user->can('ajouter_document');
    }
}
