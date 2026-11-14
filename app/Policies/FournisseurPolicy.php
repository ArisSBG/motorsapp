<?php

namespace App\Policies;

class FournisseurPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'gerer_achats';

    protected string $droitEcriture = 'gerer_achats';
}
