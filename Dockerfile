FROM php:8.2-apache

# Installer dépendances système + PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Activer modules Apache nécessaires
RUN a2enmod rewrite headers

# Installer Composer proprement
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier d’abord les fichiers Composer
COPY composer.json composer.lock* ./

# Fixer les permissions avant composer install
RUN chown -R www-data:www-data /var/www/html

# Définir variables d’environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Installer les dépendances (bloquait ici)
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --verbose || (echo "Échec installation composer" && cat /var/www/html/composer.lock && exit 1)

# Copier le reste du projet
COPY . /var/www/html

# Préparer les dossiers
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Permissions correctes
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 777 /var/www/html/var

# Entrypoint script
RUN echo '#!/bin/bash\n\
set -e\n\
php bin/console cache:clear --env=prod --no-debug || true\n\
php bin/console doctrine:database:create --if-not-exists -n || true\n\
php bin/console doctrine:schema:create -n || true\n\
php bin/console doctrine:migrations:sync-metadata-storage -n || true\n\
php bin/console doctrine:migrations:version --add --all -n || true\n\
exec apache2-foreground\n' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
