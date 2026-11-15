<x-filament-panels::page>
    @can('exporter')
    <x-filament::section>
        <x-slot name="heading">Période</x-slot>
        <x-slot name="description">Utilisée par les rapports « Bénéfice par moto » et « Ventes de la période ».</x-slot>

        <div style="display:flex; flex-wrap:wrap; gap:1rem;">
            <label style="display:flex; flex-direction:column; gap:.25rem; font-size:.875rem;">
                Du
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="du" />
                </x-filament::input.wrapper>
            </label>
            <label style="display:flex; flex-direction:column; gap:.25rem; font-size:.875rem;">
                Au
                <x-filament::input.wrapper>
                    <x-filament::input type="date" wire:model.live="au" />
                </x-filament::input.wrapper>
            </label>
        </div>
    </x-filament::section>
    @endcan

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1rem;">
        @foreach ($this->rapports() as $rapport)
            <x-filament::section wire:key="rapport-{{ $rapport['cle'] }}">
                <x-slot name="heading">{{ $rapport['titre'] }}</x-slot>
                <p style="font-size:.875rem; opacity:.75;">{{ $rapport['description'] }}</p>
                @if ($rapport['periode'])
                    <p style="margin-top:.25rem; font-size:.75rem; opacity:.6;">
                        Du {{ \Illuminate\Support\Carbon::parse($du)->format('d/m/Y') }} au {{ \Illuminate\Support\Carbon::parse($au)->format('d/m/Y') }}
                    </p>
                @endif
                <div style="margin-top:1rem;">
                    <x-filament::button tag="a" :href="$rapport['url']" target="_blank" icon="heroicon-o-printer">
                        Ouvrir le PDF
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
