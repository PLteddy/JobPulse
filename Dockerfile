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

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock en premier pour optimiser le cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances Symfony (sans les fichiers sources)
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts --no-autoloader

# Copier les fichiers du projet
COPY . .

# Finaliser l'installation de Composer avec autoloader - CORRECTION ICI
RUN composer dump-autoload --optimize --no-dev

# Configurer le DocumentRoot d'Apache avec les bonnes règles de réécriture
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride None\n\
        Require all granted\n\
        FallbackResource /index.php\n\
        <IfModule mod_rewrite.c>\n\
            Options -MultiViews\n\
            RewriteEngine On\n\
            RewriteCond %{REQUEST_FILENAME} !-f\n\
            RewriteRule ^(.*)$ index.php [QSA,L]\n\
        </IfModule>\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Préparer les répertoires avec les permissions correctes
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Créer un .htaccess approprié (backup au cas où)
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</IfModule>' > /var/www/html/public/.htaccess

# Créer le script pour nettoyer les templates - AJOUT POUR CORRIGER L'ERREUR DUMP
RUN echo '#!/bin/bash\n\
find /var/www/html/templates -type f -name "*.twig" -exec sed -i "s/{{ *dump(.*) *}}/<!-- dump removed -->/g" {} + 2>/dev/null || true\n\
' > /usr/local/bin/clean-templates.sh && chmod +x /usr/local/bin/clean-templates.sh

# Créer un script d'entrée pour gérer le démarrage
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "Initialisation de la base de données..."\n\
php bin/console doctrine:database:create --if-not-exists -n || echo "Database creation failed or already exists"\n\
php bin/console doctrine:schema:create -n || echo "Schema creation failed or already exists"\n\
php bin/console doctrine:migrations:sync-metadata-storage -n || echo "Migration sync failed"\n\
php bin/console doctrine:migrations:version --add --all -n || echo "Migration versioning failed"\n\
\n\
echo "Nettoyage des templates..."\n\
/usr/local/bin/clean-templates.sh\n\
\n\
echo "Préparation du cache..."\n\
php bin/console cache:clear --env=prod --no-debug\n\
php bin/console cache:warmup --env=prod --no-debug\n\
\n\
echo "Configuration des permissions..."\n\
chown -R www-data:www-data /var/www/html/var\n\
chmod -R 775 /var/www/html/var\n\
\n\
echo "Démarrage dApache..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Configurer les permissions adéquates
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && chmod -R 775 /var/www/html/var

# Exposer le port 80
EXPOSE 80

# Utiliser le script d'entrée comme point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]