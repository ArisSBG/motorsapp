@extends('documents.layout')
@section('titre', mb_strtoupper($titre))
@section('sous-titre', $periode)

@section('contenu')
    <table class="lignes">
        <thead>
            <tr><th>Châssis</th><th>Moto</th><th>Client</th><th>Téléphone</th><th>Vendue le</th><th>Étape</th><th class="droite">Jours</th></tr>
        </thead>
        <tbody>
            @forelse ($dossiers as $d)
                @php($jours = (int) $d->created_at->diffInDays(now()))
                <tr>
                    <td class="mono">{{ $d->moto->chassis }}</td>
                    <td>{{ $d->moto->marque }} {{ $d->moto->modele }}</td>
                    <td>{{ $d->vente?->client?->nom ?? '—' }}</td>
                    <td>{{ $d->vente?->client?->telephone ?? '—' }}</td>
                    <td>{{ $d->vente?->date_vente?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $d->statut->getLabel() }}</td>
                    <td class="droite" @if ($jours > config('entreprise.jours_alerte_papiers')) style="color:#b91c1c; font-weight:bold" @endif>{{ $jours }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="centre">Toutes les motos vendues ont leur carte grise.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="petit">En rouge : plus de {{ config('entreprise.jours_alerte_papiers') }} jours depuis la vente.</p>
@endsection
