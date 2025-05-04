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
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        FallbackResource /index.php' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        <IfModule mod_rewrite.c>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '            Options -MultiViews' >> /etc/apache2/sites-available/000-default.conf && \
    echo '            RewriteEngine On' >> /etc/apache2/sites-available/000-default.conf && \
    echo '            RewriteCond %{REQUEST_FILENAME} !-f' >> /etc/apache2/sites-available/000-default.conf && \
    echo '            RewriteRule ^(.*)$ index.php [QSA,L]' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        </IfModule>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

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
RUN echo 'display_errors = On' > /usr/local/etc/php/conf.d/error-logging.ini && \
    echo 'display_startup_errors = On' >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo 'error_reporting = E_ALL' >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo 'log_errors = On' >> /usr/local/etc/php/conf.d/error-logging.ini

# Créer un .htaccess
RUN echo '<IfModule mod_rewrite.c>' > public/.htaccess && \
    echo '    RewriteEngine On' >> public/.htaccess && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> public/.htaccess && \
    echo '    RewriteRule ^(.*)$ index.php [QSA,L]' >> public/.htaccess && \
    echo '</IfModule>' >> public/.htaccess

# Créer le script pour nettoyer les templates
RUN echo '#!/bin/bash' > /usr/local/bin/clean-templates.sh && \
    echo 'find /var/www/html/templates -type f -name "*.twig" -exec sed -i "s/{{ *dump(.*) *}}/<!-- dump removed -->/g" {} \;' >> /usr/local/bin/clean-templates.sh

RUN chmod +x /usr/local/bin/clean-templates.sh

# Configurer les permissions
RUN chown -R www-data:www-data var vendor && \
    find . -type d -exec chmod 755 {} \; && \
    find . -type f -exec chmod 644 {} \; && \
    chmod -R 777 var

# Créer le script d'entrée
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'set -e' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# Définir l'"'"'environnement si non défini' >> /usr/local/bin/entrypoint.sh && \
    echo 'if [ -z "$APP_ENV" ]; then' >> /usr/local/bin/entrypoint.sh && \
    echo '    export APP_ENV=prod' >> /usr/local/bin/entrypoint.sh && \
    echo '    echo "APP_ENV non défini, utilisation de '"'"'prod'"'"' par défaut."' >> /usr/local/bin/entrypoint.sh && \
    echo 'else' >> /usr/local/bin/entrypoint.sh && \
    echo '    echo "Environnement Symfony: $APP_ENV"' >> /usr/local/bin/entrypoint.sh && \
    echo 'fi' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# S'"'"'assurer que tout est configuré pour la production' >> /usr/local/bin/entrypoint.sh && \
    echo 'export APP_ENV=prod' >> /usr/local/bin/entrypoint.sh && \
    echo 'export APP_DEBUG=0' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo '# Afficher les variables d'"'"'environnement (sans les secrets)' >> /usr/local/bin/entrypoint.sh && \
    echo 'env | grep -v PASSWORD | grep -v SECRET | grep -v TOKEN' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Vérification de la base de données..."' >> /usr/local/bin/entrypoint.sh && \
    echo 'php bin/console doctrine:database:create --if-not-exists -n || true' >> /usr/local/bin/entrypoint.sh && \
    echo 'php bin/console doctrine:schema:create -n || true' >> /usr/local/bin/entrypoint.sh && \
    echo 'php bin/console doctrine:migrations:sync-metadata-storage -n || true' >> /usr/local/bin/entrypoint.sh && \
    echo 'php bin/console doctrine:migrations:version --add --all -n || true' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Nettoyage des templates..."' >> /usr/local/bin/entrypoint.sh && \
    echo '/usr/local/bin/clean-templates.sh' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Vérification des permissions..."' >> /usr/local/bin/entrypoint.sh && \
    echo 'chown -R www-data:www-data /var/www/html/var' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Nettoyage du cache..."' >> /usr/local/bin/entrypoint.sh && \
    echo 'APP_ENV=prod php bin/console cache:clear --no-warmup' >> /usr/local/bin/entrypoint.sh && \
    echo 'APP_ENV=prod php bin/console cache:warmup' >> /usr/local/bin/entrypoint.sh && \
    echo '' >> /usr/local/bin/entrypoint.sh && \
    echo 'echo "Démarrage du serveur..."' >> /usr/local/bin/entrypoint.sh && \
    echo 'exec apache2-foreground' >> /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port 80
EXPOSE 80

# Utiliser le script d'entrée comme point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]