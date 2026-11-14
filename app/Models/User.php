<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'telephone', 'password', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, LogsActivity, Notifiable;

    protected $attributes = [
        'actif' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    /**
     * Accès aux espaces : compte actif + profil autorisé pour ce panneau.
     * Vérifié par Filament à la connexion et à chaque requête.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->actif) {
            return false;
        }

        return $this->panneaux()->contains($panel->getId());
    }

    /** Liste des panneaux accessibles selon les rôles de l'utilisateur. */
    public function panneaux(): \Illuminate\Support\Collection
    {
        return $this->loadMissing('roles')->roles->pluck('name')
            ->map(fn (string $nom) => Role::tryFrom($nom)?->panneaux() ?? [])
            ->flatten()
            ->unique()
            ->values();
    }

    public function estAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Journal : qui a créé ou modifié un compte (jamais le mot de passe)
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'telephone', 'actif'])
            ->logOnlyDirty()
            ->useLogName('comptes');
    }
}
