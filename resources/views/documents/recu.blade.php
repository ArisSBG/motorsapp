@use('App\Support\Montant')
@use('App\Support\NombreEnLettres')
@php($client = $vente->client)
@extends('documents.layout')

@section('titre', 'REÇU DE PAIEMENT')
@section('sous-titre')
    N° <strong>{{ $paiement->numero_recu }}</strong><br>
    du {{ $paiement->date_paiement->format('d/m/Y') }}
@endsection

@section('contenu')
    <p>
        Reçu de <strong>{{ $client->nom }}</strong>
        @if ($client->telephone) (Tél. {{ $client->telephone }}) @endif
        la somme de :
    </p>

    <div class="cadre centre" style="font-size:16pt; font-weight:bold; margin: 10px 0;">
        {{ Montant::format($paiement->montant) }}
    </div>
    <p class="lettres centre">{{ ucfirst(NombreEnLettres::francsCfa($paiement->montant)) }}</p>

    <table class="lignes">
        <tr><th style="width:40%">Mode de paiement</th><td>{{ $paiement->mode->getLabel() }}@if ($paiement->reference) — réf. {{ $paiement->reference }} @endif</td></tr>
        <tr><th>Pour la facture</th><td>{{ $vente->numero }} du {{ $vente->date_vente->format('d/m/Y') }}</td></tr>
        <tr>
            <th>Moto(s)</th>
            <td>
                @foreach ($vente->lignes as $ligne)
                    {{ $ligne->moto->marque }} {{ $ligne->moto->modele }} — châssis <span class="mono">{{ $ligne->moto->chassis }}</span><br>
                @endforeach
            </td>
        </tr>
        <tr><th>Total de la facture</th><td>{{ Montant::format($vente->total()) }}</td></tr>
        <tr><th>Total payé avec ce versement</th><td>{{ Montant::format($payeALaDate) }}</td></tr>
        <tr><th>Reste à payer</th><td><strong>{{ Montant::format(max(0, $resteApres)) }}</strong>@if ($resteApres <= 0) — facture soldée @endif</td></tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <strong>Le client</strong>
            </td>
            <td class="droite">
                <strong>Pour {{ config('entreprise.nom') }}</strong><br>
                <span class="petit">Cachet et signature</span>
                @if ($paiement->encaissePar)<br><span class="petit">Encaissé par : {{ $paiement->encaissePar->name }}</span>@endif
            </td>
        </tr>
    </table>
@endsection
