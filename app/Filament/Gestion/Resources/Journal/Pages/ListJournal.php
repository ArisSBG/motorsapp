<?php

namespace App\Filament\Gestion\Resources\Journal\Pages;

use App\Filament\Gestion\Resources\Journal\JournalResource;
use Filament\Resources\Pages\ListRecords;

class ListJournal extends ListRecords
{
    protected static string $resource = JournalResource::class;
}
