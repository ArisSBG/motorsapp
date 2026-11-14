<?php

namespace App\Policies;

class FraisMotoPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'gerer_achats';

    protected string $droitEcriture = 'gerer_achats';
}
