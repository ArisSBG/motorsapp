<?php

namespace App\Filament\Gestion\Resources\Users\Tables;

use App\Enums\Role;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('Identifiant')->searchable(),
                TextColumn::make('telephone')->label('Téléphone')->placeholder('—'),
                TextColumn::make('profil')
                    ->label('Profil')
                    ->badge()
                    ->state(fn (User $record) => Role::tryFrom($record->roles->first()?->name ?? '')?->libelle() ?? '—'),
                IconColumn::make('actif')->boolean(),
                IconColumn::make('double_verification')
                    ->label('Double vérif.')
                    ->boolean()
                    ->state(fn (User $record) => filled($record->getAppAuthenticationSecret())),
            ])
            ->filters([
                TernaryFilter::make('actif')->label('Comptes actifs'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
