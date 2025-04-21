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

# Configurer le DocumentRoot d'Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/var

# Copier les fichiers du projet
COPY . /var/www/html

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer les dépendances Symfony
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN find /var/www/html -type f -exec chmod 644 {} \;

# Nettoyer le cache et chauffer le cache de production
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-warmup
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup

# Créer la base de données et exécuter les migrations si DATABASE_URL est défini
CMD php bin/console doctrine:database:create --if-not-exists -n || true \
    && php bin/console doctrine:migrations:migrate -n || true \
    && apache2-foreground