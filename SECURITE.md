# Sécurité — BURKINA MOTORS

## Mesures en place

| Domaine | Mesure |
|---|---|
| Connexion | Mots de passe hachés (bcrypt), 8 caractères minimum avec lettres et chiffres. Blocage 15 min après 5 essais ratés. Message d'erreur identique que l'identifiant existe ou non. |
| Double vérification | Application d'authentification (Google Authenticator…) avec codes de secours. Obligatoire pour l'administrateur en production, activable par tous depuis le profil. |
| Sessions | Enregistrées en base, fermées après 12 h, pas de « se souvenir de moi ». Chiffrées et en HTTPS seulement en production. |
| Appareils | L'administrateur voit les appareils connectés de chaque compte et peut tous les déconnecter (téléphone perdu ou volé). Désactiver un compte ou changer son mot de passe le déconnecte partout. |
| Droits | Vérifiés côté serveur (policies Laravel) pour chaque écran, bouton, document et rapport. Le vendeur ne voit jamais les prix d'achat ni le bénéfice. |
| Profil | Chacun change son nom et son mot de passe (avec l'ancien). L'e-mail de connexion n'est modifiable que par l'administrateur. |
| Documents scannés | Chiffrés sur le disque (clé APP_KEY), lus uniquement à travers l'application après contrôle des droits, jamais mis en cache. PDF et images uniquement, 20 Mo maximum. |
| Journal | Toute création, modification, suppression (avec ancienne valeur), connexion, échec de connexion et déconnexion. Non modifiable depuis l'application. |
| Suppressions | Corbeille de 30 jours, vidage réservé à l'administrateur. |
| Sauvegardes | Chaque nuit, archive chiffrée AES-256, 30 jours d'historique. |
| Navigateur | En-têtes : HSTS (en HTTPS), X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy (appareil photo autorisé pour le site seulement). |
| Limites de débit | Connexion (5 essais / 15 min), documents et rapports (60 / min), envois de fichiers (30 / min). |
| Téléphone | Aucune donnée gardée sur le téléphone (pas de cache des pages). |
| Production | Mode debug forcé à « non » même s'il est oublié dans le .env. |

## À faire avant la mise en ligne

- [ ] Copier `.env.production.example` en `.env`, remplir toutes les valeurs, `APP_DEBUG=false`.
- [ ] Générer `APP_KEY` et **la conserver hors du serveur** avec `BACKUP_ARCHIVE_PASSWORD` : sans elles, les documents et les sauvegardes sont illisibles.
- [ ] HTTPS actif (certificat Let's Encrypt) et redirection de HTTP vers HTTPS.
- [ ] Ports de la base de données et de Mailpit non ouverts sur Internet (ils écoutent sur 127.0.0.1 dans `compose.yaml`).
- [ ] Ne pas lancer `DemoSeeder` en production ; retirer `ADMIN_PASSWORD` du `.env` après la première installation.
- [ ] L'administrateur active la double vérification à sa première connexion (obligatoire en production).
- [ ] Brancher un stockage en ligne pour les sauvegardes (`BACKUP_DISKS=backups,s3`) et faire un test de restauration.
- [ ] Changer les mots de passe des comptes de démonstration ou les supprimer.

## Chaque mois

- [ ] Télécharger une sauvegarde sur le disque externe et tester sa restauration.
- [ ] Parcourir le journal (filtre « Échec de connexion »).
- [ ] Mettre à jour les dépendances : `composer update` puis `php artisan test`.
