#!/bin/bash
set -e

# Corriger les permissions dynamiquement à l'exécution
echo "Fixing permissions..."
mkdir -p var/cache var/log var/cache/prod var/cache/prod/twig
chown -R www-data:www-data var
chmod -R 775 var

# Continuer avec les autres initialisations
php bin/console cache:clear --no-debug || true
php bin/console doctrine:database:create --if-not-exists -n || true
php bin/console doctrine:migrations:migrate -n || true

exec apache2-foreground
