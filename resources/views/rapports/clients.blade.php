@use('App\Support\Montant')
@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', 'Situation '.$periode)

@section('contenu')
    <table class="lignes">
        <thead>
            <tr><th>Client</th><th>Téléphone</th><th>Factures en cours</th><th class="droite">Doit encore</th></tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td><strong>{{ $client->nom }}</strong></td>
                    <td>{{ $client->telephone ?: '—' }}</td>
                    <td class="petit">
                        @foreach ($client->ventes->filter(fn ($v) => $v->reste() > 0) as $v)
                            {{ $v->numero }} du {{ $v->date_vente->format('d/m/Y') }} : {{ Montant::format($v->reste()) }}<br>
                        @endforeach
                    </td>
                    <td class="droite"><strong>{{ Montant::format($client->resteDu()) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4" class="centre">Aucun client ne doit d’argent.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totaux">
        <tr><td>Clients concernés</td><td class="droite">{{ $clients->count() }}</td></tr>
        <tr class="fort"><td>Total à encaisser</td><td class="droite">{{ Montant::format($clients->sum(fn ($c) => $c->resteDu())) }}</td></tr>
    </table>
@endsection
