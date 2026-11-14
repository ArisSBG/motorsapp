<?php

namespace App\Policies;

class ClientPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'enregistrer_vente';

    protected string $droitEcriture = 'enregistrer_vente';
}
