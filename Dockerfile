FROM php:8.2-apache

# Installer les extensions PHP nécessaires et dépendances pour Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Activer Apache mod_rewrite et headers
RUN a2enmod rewrite headers

# Configurer le DocumentRoot d'Apache
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

# Installer Composer (dernière version stable)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer uniquement pour optimiser les layers
COPY composer.json composer.lock* ./

# Configurer les variables d'environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Assurer les permissions avant l’installation Composer
RUN chown -R www-data:www-data /var/www/html

# Installer les dépendances Symfony
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --verbose || (echo "Échec installation composer" && exit 1)

# Copier le reste du projet
COPY . /var/www/html

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Créer un .htaccess si besoin
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</IfModule>' > /var/www/html/public/.htaccess

# Script pour nettoyer les templates
RUN echo '#!/bin/bash\n\
find /var/www/html/templates -type f -name "*.twig" -exec sed -i "s/{{ *dump(.*) *}}/<!-- dump removed -->/g" {} \;\n\
' > /usr/local/bin/clean-templates.sh

RUN chmod +x /usr/local/bin/clean-templates.sh

# Configurer les permissions adéquates
RUN chown -R www-data:www-data /var/www/html
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN find /var/www/html -type f -exec chmod 644 {} \;
RUN chmod -R 777 /var/www/html/var

# Script d’entrée
RUN echo '#!/bin/bash\n\
set -e\n\
php bin/console cache:clear --env=prod --no-debug || true\n\
php bin/console doctrine:database:create --if-not-exists -n || true\n\
php bin/console doctrine:schema:create -n || true\n\
php bin/console doctrine:migrations:sync-metadata-storage -n || true\n\
php bin/console doctrine:migrations:version --add --all -n || true\n\
/usr/local/bin/clean-templates.sh\n\
chown -R www-data:www-data /var/www/html/var\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
