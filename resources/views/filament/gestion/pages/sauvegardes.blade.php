<x-filament-panels::page>
    <x-filament::section>
        <p class="text-sm">
            Une sauvegarde chiffrée (base de données + documents) est faite <strong>chaque nuit à 1h30</strong>
            et gardée 30 jours. Une fois par semaine, téléchargez la plus récente et copiez-la sur le
            <strong>disque dur externe</strong>.
        </p>
        <p class="text-sm mt-2 text-gray-500">
            L’archive est protégée par le mot de passe de sauvegarde, à conserver en lieu sûr hors du serveur :
            sans lui, la sauvegarde ne peut pas être restaurée.
        </p>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
