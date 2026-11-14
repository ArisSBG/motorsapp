<?php

namespace App\Policies;

class VenteLignePolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'enregistrer_vente';

    protected string $droitEcriture = 'enregistrer_vente';

    protected ?string $droitModification = 'modifier_vente';
}
