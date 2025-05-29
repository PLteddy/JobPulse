#!/bin/bash
set -e

# Définir APP_ENV si non présent
export APP_ENV=${APP_ENV:-prod}

# Attendre que la base soit dispo (optionnel)
# ./bin/console doctrine:query:sql "SELECT 1" || sleep 5

# Commandes Symfony post-deploy
php bin/console cache:clear --no-debug || true
php bin/console doctrine:database:create --if-not-exists -n || true
php bin/console doctrine:migrations:migrate -n || true

exec apache2-foreground
