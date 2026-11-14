<?php

namespace App\Filament\Gestion\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    private static function estLuiMeme(?User $record): bool
    {
        return $record !== null && $record->is(auth()->user());
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Compte')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom et prénom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Adresse e-mail (identifiant de connexion)')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('telephone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(30),
                        Select::make('role')
                            ->label('Profil')
                            ->options(Role::options())
                            ->required()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Select $component, ?User $record) => $component->state($record?->roles->first()?->name))
                            ->disabled(fn (?User $record) => self::estLuiMeme($record))
                            ->helperText(fn (?User $record) => self::estLuiMeme($record) ? 'Vous ne pouvez pas changer votre propre profil.' : 'Administrateur : tout. Vendeur et magasinier : espace Gestion. Secrétaire : espace Papiers.'),
                        Toggle::make('actif')
                            ->label('Compte actif')
                            ->default(true)
                            ->disabled(fn (?User $record) => self::estLuiMeme($record))
                            ->helperText('Désactiver le compte d’un employé qui quitte l’entreprise.'),
                    ]),
                Section::make('Mot de passe')
                    ->columns(2)
                    ->columnSpanFull()
                    ->description(fn (string $operation) => $operation === 'edit' ? 'Laisser vide pour garder le mot de passe actuel.' : null)
                    ->schema([
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirmer le mot de passe')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
