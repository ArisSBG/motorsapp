<?php

namespace App\Policies;

/** Retraits : enregistrés par la secrétaire ; corrigés ou supprimés par l'administrateur. */
class RetraitPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'suivre_papiers';

    protected string $droitEcriture = 'suivre_papiers';

    protected ?string $droitModification = 'supprimer_document';
}
