<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Politique d'accès générique : un droit pour lire, un droit pour écrire.
 * La suppression définitive (vider la corbeille) est réservée à l'administrateur.
 * Ces règles sont vérifiées côté serveur à chaque action (cahier des charges, 6.1).
 */
abstract class PolitiqueParDroit
{
    protected string $droitLecture;

    protected string $droitEcriture;

    /** Droit pour modifier ou supprimer ; par défaut le même que pour créer. */
    protected ?string $droitModification = null;

    protected function peutModifier(User $user): bool
    {
        return $user->can($this->droitModification ?? $this->droitEcriture);
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->droitLecture) || $user->can($this->droitEcriture);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can($this->droitEcriture);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->peutModifier($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->peutModifier($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->peutModifier($user);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->peutModifier($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->peutModifier($user);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->estAdmin();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->estAdmin();
    }
}
