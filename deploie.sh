#!/usr/bin/env bash
#
# Met à jour BURKINA MOTORS sur le serveur, sans perdre de données.
# À lancer depuis le dossier de l'application sur le serveur :   ./deploie.sh
#
# Étapes : sauvegarde → récupération du code → reconstruction → paquets → mode maintenance
#          → migrations → droits → caches → redémarrage des tâches → fin de maintenance.
# En cas d'erreur, le script s'arrête (la sauvegarde faite au début permet de revenir en arrière).

set -euo pipefail

app() { docker compose exec -T app "$@"; }
etape() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

[ -f .env ] || { echo "Fichier .env introuvable : lancer ce script depuis le dossier de l'application."; exit 1; }
grep -q '^APP_ENV=production' .env || { echo "APP_ENV n'est pas « production » dans .env : arrêt par sécurité."; exit 1; }

etape "1/9 Sauvegarde de la base et des documents avant la mise à jour"
app php artisan backup:run --only-db --disable-notifications || echo "(sauvegarde impossible : à vérifier, on continue)"

etape "2/9 Récupération du nouveau code (GitHub)"
git pull --ff-only

etape "3/9 Reconstruction de l'image et redémarrage des conteneurs"
docker compose build app
docker compose up -d --remove-orphans

etape "4/9 Installation des paquets PHP (sans les outils de développement)"
app composer install --no-dev --optimize-autoloader --no-interaction --no-progress

etape "5/9 Mode maintenance (les utilisateurs voient « maintenance en cours » quelques secondes)"
app php artisan down --retry=15 || true

etape "6/9 Mise à jour de la base de données (ajouts uniquement, aucune donnée effacée)"
app php artisan migrate --force

etape "7/9 Mise à jour des profils et des droits"
app php artisan db:seed --class=RolesSeeder --force

etape "8/9 Fichiers Filament et caches de production"
app php artisan filament:assets
app php artisan optimize:clear
app php artisan optimize
app php artisan filament:optimize

etape "9/9 Redémarrage des tâches de fond et fin de maintenance"
app php artisan queue:restart
docker compose restart queue scheduler nginx
app php artisan up

printf '\n\033[1;32mMise à jour terminée.\033[0m Vérifier le site, puis le journal : docker compose logs --tail=50 app\n'
