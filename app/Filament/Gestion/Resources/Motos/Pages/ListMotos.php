<?php

namespace App\Filament\Gestion\Resources\Motos\Pages;

use App\Enums\StatutMoto;
use App\Filament\Gestion\Resources\Motos\MotoResource;
use App\Models\Moto;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMotos extends ListRecords
{
    protected static string $resource = MotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'stock' => Tab::make('En stock')
                ->badge(fn () => Moto::disponibles()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', StatutMoto::EnStock)),
            'toutes' => Tab::make('Toutes'),
        ];
    }
}
