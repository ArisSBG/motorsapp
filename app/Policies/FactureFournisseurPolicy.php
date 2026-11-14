<?php

namespace App\Policies;

class FactureFournisseurPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'gerer_achats';

    protected string $droitEcriture = 'gerer_achats';
}
