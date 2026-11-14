<?php

namespace App\Policies;

class PaiementClientPolicy extends PolitiqueParDroit
{
    protected string $droitLecture = 'enregistrer_vente';

    protected string $droitEcriture = 'enregistrer_paiement_client';

    protected ?string $droitModification = 'modifier_vente';
}
