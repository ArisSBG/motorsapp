<?php

namespace App\Filament\Gestion\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Sauvegardes : liste des archives chiffrées, téléchargement (pour la copie
 * sur le disque dur externe) et sauvegarde immédiate.
 */
class Sauvegardes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $title = 'Sauvegardes';

    protected string $view = 'filament.gestion.pages.sauvegardes';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('gerer_sauvegardes');
    }

    private static function disque(): string
    {
        return explode(',', config('backup.backup.destination.disks')[0] ?? 'backups')[0];
    }

    /** @return array<int, array{chemin: string, date: int, taille: int}> */
    public function fichiers(): array
    {
        $disque = Storage::disk(self::disque());

        return collect($disque->allFiles())
            ->filter(fn (string $f) => str_ends_with($f, '.zip'))
            ->map(fn (string $f) => ['chemin' => $f, 'date' => $disque->lastModified($f), 'taille' => $disque->size($f)])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => collect($this->fichiers())->keyBy('chemin')->all())
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->formatStateUsing(fn (int $state) => date('d/m/Y à H:i', $state)),
                TextColumn::make('taille')
                    ->label('Taille')
                    ->formatStateUsing(fn (int $state) => Number::fileSize($state, precision: 1)),
                TextColumn::make('chemin')
                    ->label('Fichier')
                    ->fontFamily('mono')
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('telecharger')
                    ->label('Télécharger')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (array $record): StreamedResponse => Storage::disk(self::disque())->download($record['chemin'])),
            ])
            ->emptyStateHeading('Aucune sauvegarde pour le moment')
            ->emptyStateDescription('Une sauvegarde est faite automatiquement chaque nuit à 1h30.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sauvegarder')
                ->label('Sauvegarder maintenant')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalDescription('La sauvegarde peut prendre une minute.')
                ->action(function () {
                    $code = Artisan::call('backup:run');

                    $code === 0
                        ? Notification::make()->title('Sauvegarde terminée')->success()->send()
                        : Notification::make()->title('La sauvegarde a échoué')->body(Artisan::output())->danger()->send();
                }),
        ];
    }
}
