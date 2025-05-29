#!/bin/bash
set -e

echo "Fixing permissions and creating cache directories..."

# Créer tous les répertoires de cache nécessaires
mkdir -p var/cache/prod/twig/cc
mkdir -p var/cache/dev/twig/cc
mkdir -p var/log
mkdir -p var/sessions

# Fixer les permissions récursivement
chown -R www-data:www-data var/
chmod -R 775 var/

# S'assurer que les répertoires de cache Twig sont accessibles en écriture
chmod -R 777 var/cache/

echo "Clearing cache..."
# Vider le cache en tant que www-data
su -s /bin/bash www-data -c "php bin/console cache:clear --env=prod --no-debug" || true

echo "Setting up database..."
# Créer la base de données si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists -n || true

# Exécuter les migrations
php bin/console doctrine:migrations:migrate -n || true

echo "Final permission fix..."
# Dernière vérification des permissions
chown -R www-data:www-data var/
chmod -R 775 var/

echo "Starting Apache..."
exec apache2-foreground