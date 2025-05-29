FROM php:8.2-apache

# Installer les extensions PHP nécessaires
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

# Activer les modules Apache
RUN a2enmod rewrite headers

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Créer les répertoires var avec les bonnes permissions
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Installer les dépendances Composer sans exécuter les scripts
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Fixer le DocumentRoot Apache vers /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Entrypoint pour initialisation dynamique (cache, DB, migrations)
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
