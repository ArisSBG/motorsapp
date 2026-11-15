# Mettre à jour le site en ligne (motorsapp.sacmh.bf)

Principe : on envoie le code sur GitHub depuis le PC, puis le serveur le récupère et lance `deploie.sh`.
Les données (base, documents, sauvegardes) restent sur le serveur et ne sont jamais écrasées.

## 1. Sur le PC : envoyer les correctifs sur GitHub

```bash
cd "D:/Projet motors/app"
git status                      # vérifier qu'aucun fichier .env n'apparaît
git add -A
git add --chmod=+x deploie.sh   # rend le script exécutable sur le serveur
git commit -m "Lots 1 à 4, sécurité, feuille UAT, script de déploiement"
git push
```

## 2. Sur le serveur, la première fois seulement : compléter le .env

Comparer le `.env` du serveur avec `.env.production.example` et **ajouter ce qui manque**, en particulier :

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://motorsapp.sacmh.bf
APP_TIMEZONE=Africa/Ouagadougou
APP_FALLBACK_LOCALE=en

SESSION_LIFETIME=720
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

# Le site passe par Cloudflare : faire confiance au proxy pour l'IP réelle et le HTTPS
TRUSTED_PROXIES=*

BACKUP_ARCHIVE_PASSWORD=          # long et aléatoire, à noter HORS du serveur
BACKUP_DISKS=backups

ENTREPRISE_IFU=
ENTREPRISE_RCCM=
```

`TRUSTED_PROXIES=*` suppose que le serveur n'est joignable **que** par Cloudflare (ou par le proxy local qui reçoit
le trafic de Cloudflare). Si le serveur est aussi joignable directement par son adresse IP, remplacer `*` par les
plages d'adresses de Cloudflare (https://www.cloudflare.com/ips/), séparées par des virgules.

**À conserver hors du serveur** (papier, coffre, gestionnaire de mots de passe) : `APP_KEY` et
`BACKUP_ARCHIVE_PASSWORD`. Sans eux, les documents scannés et les sauvegardes sont illisibles.
Ne jamais régénérer `APP_KEY` sur un site qui a déjà des documents.

## 3. Sur le serveur, à chaque mise à jour

```bash
cd /chemin/vers/app       # dossier où le dépôt a été cloné
./deploie.sh
```

Le script : sauvegarde la base → `git pull` → reconstruit l'image → installe les paquets → met le site en
maintenance quelques secondes → applique les nouvelles tables → met à jour les droits → construit les caches →
redémarre les tâches de fond → remet le site en ligne.

Si le script s'arrête sur une erreur : lire le message, corriger, relancer. Pour remettre le site en ligne en
urgence : `docker compose exec app php artisan up`.

**Ne jamais lancer** sur le serveur : `migrate:fresh`, `migrate:refresh`, `db:wipe` (effacent toutes les données),
ni `db:seed --class=DemoSeeder` (données de démonstration), ni `php artisan test` (les tests utilisent leur propre
base en mémoire, mais ils n'ont rien à faire en production).

## 4. Vérifier après la mise à jour

- [ ] La page de connexion n'a plus la case « Se souvenir de moi ».
- [ ] Menu Administration > Utilisateurs : le bouton « Appareils connectés » existe.
- [ ] Administration > Journal : votre connexion apparaît (avec votre adresse IP réelle, pas celle de Cloudflare).
- [ ] Administration > Sauvegardes > « Sauvegarder maintenant » fonctionne.
- [ ] Depuis un PC : `curl -sI https://motorsapp.sacmh.bf/gestion/login` montre `strict-transport-security` et des
      cookies marqués `secure`.

## 5. Réglages Cloudflare (tableau de bord Cloudflare > sacmh.bf)

| Menu | Réglage | Pourquoi |
|---|---|---|
| SSL/TLS > Vue d'ensemble | **Complet (strict)** si le serveur a un certificat (Let's Encrypt ou certificat d'origine Cloudflare), sinon « Complet ». Éviter « Flexible ». | En « Flexible », le trafic entre Cloudflare et le serveur n'est pas chiffré. |
| SSL/TLS > Certificats Edge | **Toujours utiliser HTTPS** : activé. Version TLS minimale : 1.2. | Pas de connexion en clair. |
| Vitesse > Optimisation | **Rocket Loader : désactivé.** | Il réordonne les scripts et casse Livewire/Alpine (boutons et formulaires qui ne réagissent plus). |
| Mise en cache | Ne pas créer de règle « Cache Everything » sur `motorsapp`. Ajouter une règle « Contourner le cache » pour `motorsapp.sacmh.bf/documents/*` et `/livewire/*`. | Les pages et documents sont personnels : ils ne doivent jamais être gardés en cache par Cloudflare. |
| Sécurité > WAF > Règles de limitation de débit | (Optionnel) 10 requêtes / minute par IP sur `/gestion/login` et `/papiers/login`. | Ralentit les essais de mots de passe en plus du blocage de l'application. |
| DNS | L'enregistrement `motorsapp` doit être **proxifié** (nuage orange). | Masque l'adresse du serveur et protège contre les attaques. |

Les envois de fichiers (20 Mo maximum dans l'application) passent sans problème : la limite de Cloudflare
en offre gratuite est de 100 Mo.
