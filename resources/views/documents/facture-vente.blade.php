@use('App\Support\Montant')
@use('App\Support\NombreEnLettres')
@php($client = $vente->client)
@extends('documents.layout')

@section('titre', $vente->statut === \App\Enums\StatutVente::Reservation ? 'BON DE RÉSERVATION' : 'FACTURE')
@section('sous-titre')
    N° <strong>{{ $vente->numero }}</strong><br>
    du {{ $vente->date_vente->format('d/m/Y') }}
@endsection

@section('contenu')
    <table style="width:100%">
        <tr>
            <td style="width:50%"></td>
            <td class="cadre">
                <div class="titre-cadre">Client</div>
                <strong>{{ $client->nom }}</strong><br>
                @if ($client->telephone) Tél. {{ $client->telephone }}<br> @endif
                @if ($client->adresse) {{ $client->adresse }}<br> @endif
                @if ($client->numero_piece)
                    {{ $client->type_piece?->getLabel() ?? 'Pièce' }} n° {{ $client->numero_piece }}
                @endif
            </td>
        </tr>
    </table>

    <table class="lignes">
        <thead>
            <tr>
                <th style="width:5%">N°</th>
                <th>Désignation</th>
                <th>N° de châssis</th>
                <th>N° moteur</th>
                <th class="droite" style="width:20%">Prix (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vente->lignes as $i => $ligne)
                <tr>
                    <td class="centre">{{ $i + 1 }}</td>
                    <td>
                        Moto {{ $ligne->moto->marque }} {{ $ligne->moto->modele }}
                        @if ($ligne->moto->couleur) — {{ $ligne->moto->couleur }} @endif
                        @if ($ligne->moto->cylindree) — {{ $ligne->moto->cylindree }} cm³ @endif
                    </td>
                    <td class="mono">{{ $ligne->moto->chassis }}</td>
                    <td class="mono">{{ $ligne->moto->numero_moteur ?: '—' }}</td>
                    <td class="droite">{{ Montant::format($ligne->prix_vente, devise: false) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totaux">
        <tr class="fort">
            <td>Total</td>
            <td class="droite">{{ Montant::format($vente->total()) }}</td>
        </tr>
        <tr>
            <td>Déjà payé</td>
            <td class="droite">{{ Montant::format($vente->paye()) }}</td>
        </tr>
        <tr>
            <td><strong>Reste à payer</strong></td>
            <td class="droite"><strong>{{ Montant::format($vente->reste()) }}</strong></td>
        </tr>
    </table>

    <p class="lettres">
        Arrêtée la présente {{ $vente->statut === \App\Enums\StatutVente::Reservation ? 'réservation' : 'facture' }}
        à la somme de <strong>{{ ucfirst(NombreEnLettres::francsCfa($vente->total())) }}</strong>.
    </p>

    @if ($vente->notes)
        <p class="petit">Observations : {{ $vente->notes }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <strong>Le client</strong><br>
                <span class="petit">« Lu et approuvé »</span>
            </td>
            <td class="droite">
                <strong>Pour {{ config('entreprise.nom') }}</strong><br>
                <span class="petit">Cachet et signature</span>
                @if ($vente->vendeur)<br><span class="petit">Vendeur : {{ $vente->vendeur->name }}</span>@endif
            </td>
        </tr>
    </table>
@endsection
