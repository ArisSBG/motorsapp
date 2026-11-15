@use('App\Support\Montant')
@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', 'Dettes '.$periode)

@section('contenu')
    <table class="lignes">
        <thead>
            <tr><th>Fournisseur</th><th>Factures non soldées</th><th class="droite">Reste à payer</th></tr>
        </thead>
        <tbody>
            @forelse ($fournisseurs as $f)
                <tr>
                    <td><strong>{{ $f->nom }}</strong><br><span class="petit">{{ $f->telephone }}</span></td>
                    <td class="petit">
                        @foreach ($f->facturesDues as $fa)
                            N° {{ $fa->numero }} du {{ $fa->date_facture->format('d/m/Y') }} :
                            {{ Montant::format($fa->solde()) }} sur {{ Montant::format($fa->total()) }}<br>
                        @endforeach
                    </td>
                    <td class="droite"><strong>{{ Montant::format($f->resteAPayer()) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="3" class="centre">Aucune dette fournisseur.</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totaux">
        <tr class="fort"><td>Total dû aux fournisseurs</td><td class="droite">{{ Montant::format($fournisseurs->sum(fn ($f) => $f->resteAPayer())) }}</td></tr>
    </table>
@endsection
