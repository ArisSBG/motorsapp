<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Appareils connectés d'un utilisateur (sessions enregistrées en base).
 * Permet à l'administrateur de déconnecter un téléphone perdu ou volé (cahier des charges, 6.1).
 */
class SessionsUtilisateur
{
    /** @return list<array{ip: ?string, appareil: string, derniere_activite: CarbonImmutable}> */
    public function liste(User $user): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => [
                'ip' => $s->ip_address,
                'appareil' => self::decrireAppareil((string) $s->user_agent),
                'derniere_activite' => CarbonImmutable::createFromTimestamp($s->last_activity),
            ])
            ->all();
    }

    /** Ferme toutes les sessions et invalide le cookie « se souvenir de moi ». */
    public function deconnecterPartout(User $user): int
    {
        $nombre = DB::table('sessions')->where('user_id', $user->getKey())->delete();

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        activity('connexions')
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->event('deconnexion_forcee')
            ->withProperties(['sessions_fermees' => $nombre])
            ->log('Déconnexion de tous les appareils');

        return $nombre;
    }

    public static function decrireAppareil(string $userAgent): string
    {
        $systeme = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iPhone / iPad',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'Mac',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Appareil inconnu',
        };
        $navigateur = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'navigateur',
        };

        return "{$navigateur} sur {$systeme}";
    }
}
