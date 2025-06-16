#!/bin/bash
set -e

echo "🚀 Starting JobPulse application..."

echo "📁 Fixing permissions and creating cache directories..."
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

echo "📦 Installing JavaScript assets..."
# Installer les assets JavaScript manquants
php bin/console importmap:install || true

echo "🔧 Setting up database..."
# Créer la base de données si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists -n || true

# Fonction intelligente pour gérer les tables
setup_database_tables() {
    echo "🔍 Checking if tables exist..."
    
    # Vérifier si la table 'poste' existe
    table_exists=$(php -r "
        try {
            \$entityManager = require 'bootstrap_db_check.php';
            \$connection = \$entityManager->getConnection();
            \$schemaManager = \$connection->createSchemaManager();
            \$tables = \$schemaManager->listTableNames();
            echo in_array('poste', \$tables) ? '1' : '0';
        } catch (Exception \$e) {
            // Fallback avec requête SQL directe
            try {
                \$dsn = \$_ENV['DATABASE_URL'] ?? '';
                if (empty(\$dsn)) exit('0');
                \$pdo = new PDO(\$dsn);
                \$result = \$pdo->query(\"SELECT to_regclass('public.poste')\");
                \$exists = \$result->fetchColumn();
                echo \$exists ? '1' : '0';
            } catch (Exception \$e2) {
                echo '0';
            }
        }
    ") || echo "0"
    
    if [ "$table_exists" = "0" ]; then
        echo "📋 No tables found, creating database schema..."
        
        # Essayer d'abord les migrations si elles existent
        if php bin/console doctrine:migrations:status --no-interaction 2>/dev/null | grep -q "New"; then
            echo "🔄 Running migrations..."
            php bin/console doctrine:migrations:migrate -n || {
                echo "⚠️ Migrations failed, creating schema directly..."
                php bin/console doctrine:schema:create --env=prod -n || {
                    echo "❌ Schema creation failed"
                    exit 1
                }
            }
        else
            echo "📋 No migrations found, creating schema directly..."
            php bin/console doctrine:schema:create --env=prod -n || {
                echo "❌ Schema creation failed"
                exit 1
            }
        fi
        
        echo "✅ Database schema created successfully"
    else
        echo "✅ Tables already exist"
        
        # Si les tables existent, essayer les migrations quand même (au cas où il y aurait des updates)
        echo "🔄 Checking for pending migrations..."
        php bin/console doctrine:migrations:migrate -n || echo "⚠️ No migrations to run or migration failed (might be normal)"
    fi
}

# Créer un fichier bootstrap temporaire pour vérifier les tables
cat > bootstrap_db_check.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;

// Charger les variables d'environnement
if (file_exists('.env')) {
    $dotenv = new Dotenv();
    $dotenv->load('.env');
}

// Configuration Doctrine simple
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/src/Entity'],
    isDevMode: false,
);

$connection = DriverManager::getConnection([
    'url' => $_ENV['DATABASE_URL'] ?? '',
]);

return new EntityManager($connection, $config);
EOF

# Exécuter la configuration de la base de données
setup_database_tables

# Nettoyer le fichier temporaire
rm -f bootstrap_db_check.php

echo "🧹 Clearing cache..."
# Vider le cache en tant que www-data
su -s /bin/bash www-data -c "php bin/console cache:clear --env=prod --no-debug" || true

echo "🔐 Final permission fix..."
# Dernière vérification des permissions
chown -R www-data:www-data var/
chmod -R 775 var/

echo "✅ Application ready!"
echo "🌐 Starting Apache..."
exec apache2-foreground