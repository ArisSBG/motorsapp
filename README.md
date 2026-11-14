# BURKINA MOTORS — Application de gestion

Application web à deux espaces : **Gestion** (stock, achats, ventes, bénéfice) et **Papiers** (immatriculations, archivage).

- Laravel 13 · Filament 5 · Livewire 4 · PHP 8.4 · MariaDB 11.4
- Tout tourne dans Docker (`compose.yaml`).

## Démarrer en local

Prérequis : Docker Desktop lancé.

```bash
cp .env.example .env          # puis remplir DB_PASSWORD, DB_ROOT_PASSWORD, ADMIN_EMAIL, ADMIN_PASSWORD
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

| Adresse | Contenu |
|---|---|
| http://localhost:8080/gestion | Espace Gestion |
| http://localhost:8080/papiers | Espace Papiers |
| http://localhost:8025 | E-mails de test (Mailpit, `docker compose --profile dev up -d`) |

Le premier administrateur est créé avec `ADMIN_EMAIL` / `ADMIN_PASSWORD` du `.env`.
En local, le seeder crée aussi un compte de démo par profil (`vendeur@`, `secretaire@`, `magasinier@burkina-motors.test`, même mot de passe).

Données de démonstration (local) : `docker compose exec app php artisan db:seed --class=DemoSeeder`.

## Commandes utiles

```bash
docker compose exec app php artisan test          # tests automatiques
docker compose exec app php artisan migrate       # nouvelles tables
docker compose logs -f app                        # journaux
docker compose stop queue scheduler               # alléger la machine pendant le développement
```

## Organisation du code

| Dossier | Contenu |
|---|---|
| `app/Filament/Gestion` | Écrans de l'espace Gestion |
| `app/Filament/Papiers` | Écrans de l'espace Papiers |
| `app/Filament/Auth/Login.php` | Connexion (blocage 15 min après 5 essais) |
| `app/Enums/Role.php` | Les 4 profils et leurs espaces |
| `database/seeders/RolesSeeder.php` | Droits de chaque profil |
| `app/Models` | Motos, fournisseurs, factures, versements, frais |
| `app/Services/RepartitionTransport.php` | Transport d'une facture réparti à parts égales |
| `app/Policies` | Droits vérifiés côté serveur pour chaque écran |
| `routes/console.php` | Tâches de nuit : sauvegarde, corbeille |
| `docker/` | Image PHP, configuration nginx |

Les tests tournent sur une base SQLite en mémoire (`phpunit.xml`) : ils ne touchent jamais la base de développement.

`vendor/` est dans un volume Docker (rapide sous Windows) : installer les paquets avec
`docker compose exec app composer require …`.
