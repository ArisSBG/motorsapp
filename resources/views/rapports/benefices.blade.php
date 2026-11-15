@use('App\Support\Montant')
@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', 'Ventes soldées '.$periode)

@section('contenu')
    <p class="petit">Le bénéfice est compté le jour où la vente est entièrement payée. Coûts = prix d’achat + frais de la moto (transport, réparations) + immatriculation + casque + commission.</p>
    <table class="lignes">
        <thead>
            <tr>
                <th>Soldée le</th><th>Facture</th><th>Client</th><th>Moto(s)</th>
                <th class="droite">Prix de vente</th><th class="droite">Coûts</th><th class="droite">Bénéfice</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ventes as $vente)
                <tr>
                    <td>{{ $vente->soldee_le->format('d/m/Y') }}</td>
                    <td class="mono">{{ $vente->numero }}</td>
                    <td>{{ $vente->client->nom }}</td>
                    <td>
                        @foreach ($vente->lignes as $l)
                            {{ $l->moto->marque }} {{ $l->moto->modele }} <span class="mono petit">{{ $l->moto->chassis }}</span><br>
                        @endforeach
                    </td>
                    <td class="droite">{{ Montant::format($vente->total(), false) }}</td>
                    <td class="droite">{{ Montant::format($vente->couts(), false) }}</td>
                    <td class="droite"><strong>{{ Montant::format($vente->benefice(), false) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" class="centre">Aucune vente soldée sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totaux">
        <tr><td>Ventes soldées</td><td class="droite">{{ $ventes->count() }}</td></tr>
        <tr><td>Chiffre d’affaires</td><td class="droite">{{ Montant::format($ventes->sum(fn ($v) => $v->total())) }}</td></tr>
        <tr class="fort"><td>Bénéfice total</td><td class="droite">{{ Montant::format($ventes->sum(fn ($v) => $v->benefice())) }}</td></tr>
    </table>
@endsection
