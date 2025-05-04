FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer Apache mod_rewrite et headers
RUN a2enmod rewrite headers

# Configurer le DocumentRoot d'Apache
RUN cat > /etc/apache2/sites-available/000-default.conf << 'EOL'
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
        <IfModule mod_rewrite.c>
            Options -MultiViews
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOL

# Préparer le répertoire de travail
WORKDIR /var/www/html

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier d'abord les fichiers composer pour tirer parti du cache de Docker
COPY composer.json composer.lock ./

# Créer les répertoires nécessaires avec les permissions appropriées
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var

# Installer les dépendances en mode production
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts --no-autoloader

# Copier le reste des fichiers du projet
COPY . .

# Finaliser l'installation de Composer
RUN APP_ENV=prod composer dump-autoload --optimize --no-dev --classmap-authoritative

# Configurer PHP pour la journalisation des erreurs
RUN cat > /usr/local/etc/php/conf.d/error-logging.ini << 'EOL'
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
EOL

# Créer un .htaccess
RUN cat > public/.htaccess << 'EOL'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
EOL

# Créer le script pour nettoyer les templates
RUN cat > /usr/local/bin/clean-templates.sh << 'EOL'
#!/bin/bash
find /var/www/html/templates -type f -name "*.twig" -exec sed -i "s/{{ *dump(.*) *}}/<!-- dump removed -->/g" {} \;
EOL

RUN chmod +x /usr/local/bin/clean-templates.sh

# Configurer les permissions
RUN chown -R www-data:www-data var vendor && \
    find . -type d -exec chmod 755 {} \; && \
    find . -type f -exec chmod 644 {} \; && \
    chmod -R 777 var

# Créer le script d'entrée
RUN cat > /usr/local/bin/entrypoint.sh << 'EOL'
#!/bin/bash
set -e

# Définir l'environnement si non défini
if [ -z "$APP_ENV" ]; then
    export APP_ENV=prod
    echo "APP_ENV non défini, utilisation de 'prod' par défaut."
else
    echo "Environnement Symfony: $APP_ENV"
fi

# S'assurer que tout est configuré pour la production
export APP_ENV=prod
export APP_DEBUG=0

# Afficher les variables d'environnement (sans les secrets)
env | grep -v PASSWORD | grep -v SECRET | grep -v TOKEN

echo "Vérification de la base de données..."
php bin/console doctrine:database:create --if-not-exists -n || true
php bin/console doctrine:schema:create -n || true
php bin/console doctrine:migrations:sync-metadata-storage -n || true
php bin/console doctrine:migrations:version --add --all -n || true

echo "Nettoyage des templates..."
/usr/local/bin/clean-templates.sh

echo "Vérification des permissions..."
chown -R www-data:www-data /var/www/html/var

echo "Nettoyage du cache..."
APP_ENV=prod php bin/console cache:clear --no-warmup
APP_ENV=prod php bin/console cache:warmup

echo "Démarrage du serveur..."
exec apache2-foreground
EOL

RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port 80
EXPOSE 80

# Utiliser le script d'entrée comme point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]