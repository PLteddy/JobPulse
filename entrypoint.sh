#!/bin/bash
set -e

# Assurer les bonnes permissions au runtime (au cas où Railway override les droits)
mkdir -p var/cache var/log
chown -R www-data:www-data var
chmod -R 775 var

# Commandes Symfony post-deploy
php bin/console cache:clear --no-debug || true
php bin/console doctrine:database:create --if-not-exists -n || true
php bin/console doctrine:migrations:migrate -n || true

exec apache2-foreground
