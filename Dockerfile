FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Activer Apache mod_rewrite
RUN a2enmod rewrite
RUN mkdir -p /var/www/html/var && chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# Copier les fichiers du projet dans le conteneur
COPY . /var/www/html

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer Composer (depuis le Docker Composer officiel)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer les dépendances Symfony
RUN composer install --no-dev --optimize-autoloader --prefer-dist --no-scripts

# Droits
RUN chown -R www-data:www-data /var/www/html/var /var/www/html/vendor

# Exposer le port 80
EXPOSE 80
