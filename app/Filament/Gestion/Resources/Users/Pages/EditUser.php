<?php

namespace App\Filament\Gestion\Resources\Users\Pages;

use App\Filament\Gestion\Resources\Users\ActionDeconnexion;
use App\Filament\Gestion\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionDeconnexion::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Un administrateur ne peut pas se désactiver lui-même
        if ($this->record->is(auth()->user())) {
            $data['actif'] = true;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record->is(auth()->user()) && filled($this->data['role'] ?? null)) {
            $this->record->syncRoles([$this->data['role']]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
