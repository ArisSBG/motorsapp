<?php

namespace App\Policies;

class VentePolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'enregistrer_vente';

    protected string $droitEcriture = 'enregistrer_vente';

    protected ?string $droitModification = 'modifier_vente';
}
