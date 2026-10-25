# Règles du projet (agents)

## Règle absolue — commandes impactant la base de données
Ne JAMAIS exécuter moi-même une commande qui modifie la base de données
(`migrate`, `migrate:fresh`, `migrate:refresh`, `db:seed`, `db:wipe`,
`drop`, `truncate`, suppressions/écritures directes, etc.), ici ou sur
tout autre projet.

Si une telle commande est nécessaire : la formuler prête à l'emploi et
demander à l'utilisateur de l'exécuter lui-même.

Cas typique (runs de tests sur `keduc_test`) : les Feature Tests utilisent
`RefreshDatabase` qui migre automatiquement la base de test — aucune commande
manuelle n'est requise. Ne jamais lancer `artisan migrate:fresh` avec
`--env=testing` : sans `.env.testing`, Laravel retombe sur `.env` et
réinitialise la base de développement `keduc` (incident du 14/08/2026).

## Environnement
- PHP : `/mnt/c/xampp/php/php.exe` ; commandes Laravel à exécuter depuis `backend/`
- BDD centrale (Landlord) : PostgreSQL `saascd_plateforme` (`DB_HOST=127.0.0.1`,
  `DB_PORT=5432`, `DB_USERNAME=postgres`, mot de passe dans `backend/.env`)
- BDD des cabinets : créées dynamiquement par stancl (`cabinet_<slug>`) ; le compte
  `postgres` doit rester superuser local (`CREATEDB`).
- **Migrations tenants** : l'option `--tenants` de `tenants:migrate` est un tableau
  Symfony (`VALUE_IS_ARRAY`) — la virgule n'est PAS un séparateur. `--tenants=c1,magis-plus-center`
  est lu comme un seul ID inexistant → `TenantCouldNotBeIdentifiedById`. Syntaxe correcte :
  `php artisan tenants:migrate --force` (tous) ou
  `php artisan tenants:migrate --tenants=c1 --tenants=magis-plus-center --force`.
- Tests (D-003/D-023) : `phpunit.xml` = pgsql, base centrale de test `keduc_test`,
  préfixe tenant `keduc_test_` (env `TENANCY_DB_PREFIX`, saisie en `force="true"` — les
  `<env>` PHPUnit doivent surcharger le `.env` chargé par `artisan test`). Base centrale
  NON transactée (PostgreSQL interdit `CREATE DATABASE` en transaction) ; `migrate:fresh`
  avant chaque test ; bases tenant `keduc_test_<slug>` créées par le pipeline et supprimées
  en tearDown. **Ne pas exécuter `php artisan config:cache`** : la config en cache ignorerait
  les `<env>` PHPUnit (incident test du 24/09/2026). Suite Legacy KEduc (195 tests monolithe
  web) déplacée dans `tests/Feature/Legacy/` et exclue — réécrite par module API dès P3.
- Langue de travail : français.
