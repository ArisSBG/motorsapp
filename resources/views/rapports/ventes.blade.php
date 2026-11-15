@use('App\Support\Montant')
@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', $periode)

@section('contenu')
    <table class="lignes">
        <thead>
            <tr>
                <th>Date</th><th>Facture</th><th>Client</th><th>Moto(s)</th><th>Vendeur</th>
                <th class="droite">Total</th><th class="droite">Payé</th><th class="droite">Reste</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ventes as $vente)
                <tr>
                    <td>{{ $vente->date_vente->format('d/m/Y') }}</td>
                    <td class="mono">{{ $vente->numero }}</td>
                    <td>{{ $vente->client->nom }}</td>
                    <td>@foreach ($vente->lignes as $l){{ $l->moto->marque }} {{ $l->moto->modele }}<br>@endforeach</td>
                    <td>{{ $vente->vendeur?->name ?? '—' }}</td>
                    <td class="droite">{{ Montant::format($vente->total(), false) }}</td>
                    <td class="droite">{{ Montant::format($vente->paye(), false) }}</td>
                    <td class="droite">{{ $vente->reste() > 0 ? Montant::format($vente->reste(), false) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="centre">Aucune vente sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totaux">
        <tr><td>Ventes</td><td class="droite">{{ $ventes->count() }}</td></tr>
        <tr><td>Motos vendues</td><td class="droite">{{ $ventes->sum(fn ($v) => $v->lignes->count()) }}</td></tr>
        <tr class="fort"><td>Chiffre d’affaires</td><td class="droite">{{ Montant::format($ventes->sum(fn ($v) => $v->total())) }}</td></tr>
        <tr><td>Reste à encaisser</td><td class="droite">{{ Montant::format($ventes->sum(fn ($v) => $v->reste())) }}</td></tr>
    </table>
@endsection
