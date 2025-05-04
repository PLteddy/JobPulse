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

# Configurer le DocumentRoot d'Apache avec les bonnes règles de réécriture
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
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

# Préparer le répertoire de travail
WORKDIR /var/www/html

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier d'abord les fichiers composer pour tirer parti du cache de Docker
COPY composer.json composer.lock ./

# Créer le répertoire var avec les permissions appropriées AVANT d'exécuter composer
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data var

# Installer les dépendances (cela va créer le répertoire vendor)
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts --no-autoloader

# Maintenant, copiez le reste des fichiers du projet
COPY . .

# Finalisez l'installation de Composer
RUN APP_ENV=prod composer dump-autoload --optimize --no-dev --classmap-authoritative

# Configurer PHP pour afficher les erreurs en production
RUN { \
    echo 'display_errors = On'; \
    echo 'display_startup_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/error-logging.ini

# Créer un .htaccess approprié si nécessaire
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</IfModule>' > public/.htaccess

# Créer le script pour nettoyer les templates
RUN echo '#!/bin/bash\n\
find /var/www/html/templates -type f -name "*.twig" -exec sed -i "s/{{ *dump(.*) *}}/<!-- dump removed -->/g" {} \;\n\
' > /usr/local/bin/clean-templates.sh

RUN chmod +x /usr/local/bin/clean-templates.sh

# MAINTENANT on peut changer les permissions sur vendor car il existe
RUN chown -R www-data:www-data var vendor

# Configurer les permissions adéquates pour le reste des fichiers
RUN find . -type d -exec chmod 755 {} \;
RUN find . -type f -exec chmod 644 {} \;
RUN chmod -R 777 var

# Créer un script d'entrée pour gérer le démarrage
RUN echo '#!/bin/bash\n\
set -e\n\
# Définir l\'environnement si non défini\n\
if [ -z "$APP_ENV" ]; then\n\
    export APP_ENV=prod\n\
    echo "APP_ENV non défini, utilisation de \'prod\' par défaut."\n\
else\n\
    echo "Environnement Symfony: $APP_ENV"\n\
fi\n\
\n\
# S\'assurer que tout est configuré pour la production\n\
export APP_ENV=prod\n\
export APP_DEBUG=0\n\
\n\
# Afficher les variables d\'environnement (sans les secrets)\n\
env | grep -v PASSWORD | grep -v SECRET | grep -v TOKEN\n\
\n\
echo "Vérification de la base de données..."\n\
php bin/console doctrine:database:create --if-not-exists -n || true\n\
php bin/console doctrine:schema:create -n || true\n\
php bin/console doctrine:migrations:sync-metadata-storage -n || true\n\
php bin/console doctrine:migrations:version --add --all -n || true\n\
\n\
echo "Nettoyage des templates..."\n\
/usr/local/bin/clean-templates.sh\n\
\n\
echo "Vérification des permissions..."\n\
chown -R www-data:www-data /var/www/html/var\n\
\n\
echo "Nettoyage du cache..."\n\
APP_ENV=prod php bin/console cache:clear --no-warmup\n\
APP_ENV=prod php bin/console cache:warmup\n\
\n\
echo "Démarrage du serveur..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port 80
EXPOSE 80

# Utiliser le script d'entrée comme point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]