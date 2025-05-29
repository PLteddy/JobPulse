FROM php:8.2-apache

# Installer les paquets nécessaires
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

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet (sans le vendor/ si .dockerignore est bien configuré)
COPY . /var/www/html

# Installer les dépendances Symfony en mode prod
RUN composer install --no-dev --optimize-autoloader

# Fixer le DocumentRoot Apache vers le dossier public/
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Créer les dossiers nécessaires avec les bons droits
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/var

# Entrypoint script pour initialiser la base / le cache / les migrations
RUN echo '#!/bin/bash\n\
set -e\n\
php bin/console cache:clear --env=prod --no-debug || true\n\
php bin/console doctrine:database:create --if-not-exists -n || true\n\
php bin/console doctrine:schema:create -n || true\n\
php bin/console doctrine:migrations:sync-metadata-storage -n || true\n\
php bin/console doctrine:migrations:version --add --all -n || true\n\
exec apache2-foreground\n' > /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port HTTP
EXPOSE 80

# Démarrer via l'entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
