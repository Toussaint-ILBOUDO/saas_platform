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
- Tests : base PostgreSQL `keduc_test` (base centrale de test) + bases tenant
  créées dynamiquement par les tests d'isolation ; `phpunit.xml` restauré en
  sqlite `:memory:` (ne s'exécute pas : les migrations utilisent `dropColumn`).
- Langue de travail : français.
