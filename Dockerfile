FROM php:8.2-apache

# Installer dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    libgmp-dev \
    zlib1g-dev \
    libssl-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_mysql zip gmp

# Activer Apache modules
RUN a2enmod rewrite headers

# Installer Composer proprement (direct depuis getcomposer.org)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock avant install
COPY composer.json composer.lock* ./

# Fixer les permissions
RUN chown -R www-data:www-data /var/www/html

# Variables d’environnement
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# **Installer les dépendances Composer**
RUN composer clear-cache \
    && composer self-update \
    && composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction --verbose \
    || (echo "Échec installation composer" && exit 1)

# Copier le reste des fichiers
COPY . /var/www/html

# Préparer les répertoires nécessaires
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html \
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
