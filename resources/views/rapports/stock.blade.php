@use('App\Support\Montant')
@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', $periode)

@section('contenu')
    <table class="lignes">
        <thead>
            <tr>
                <th>N°</th><th>Châssis</th><th>Marque</th><th>Modèle</th><th>Couleur</th><th>Fournisseur</th><th>Reçue le</th>
                @if ($avecPrix)<th class="droite">Prix d’achat</th><th class="droite">Coût de revient</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($motos as $i => $m)
                <tr>
                    <td class="centre">{{ $i + 1 }}</td>
                    <td class="mono">{{ $m->chassis }}</td>
                    <td>{{ $m->marque }}</td>
                    <td>{{ $m->modele }}</td>
                    <td>{{ $m->couleur ?: '—' }}</td>
                    <td>{{ $m->fournisseur?->nom }}</td>
                    <td>{{ $m->date_reception->format('d/m/Y') }}</td>
                    @if ($avecPrix)
                        <td class="droite">{{ Montant::format($m->prix_achat, false) }}</td>
                        <td class="droite">{{ Montant::format($m->coutDeRevient(), false) }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="9" class="centre">Aucune moto en stock.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totaux">
        <tr><td>Motos en stock</td><td class="droite">{{ $motos->count() }}</td></tr>
        @if ($avecPrix)
            <tr class="fort"><td>Valeur (prix d’achat)</td><td class="droite">{{ Montant::format($motos->sum('prix_achat')) }}</td></tr>
        @endif
    </table>
    <p class="petit">
        Par modèle :
        @foreach ($motos->groupBy(fn ($m) => $m->marque.' '.$m->modele) as $modele => $groupe)
            {{ $modele }} ({{ $groupe->count() }})@if (! $loop->last), @endif
        @endforeach
    </p>
@endsection
