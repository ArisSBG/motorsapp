<?php

namespace App\Policies;

class EcheancePolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'enregistrer_vente';

    protected string $droitEcriture = 'enregistrer_vente';
}
