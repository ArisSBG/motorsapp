<?php

namespace App\Policies;

class MotoPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'voir_stock';

    protected string $droitEcriture = 'gerer_achats';
}
