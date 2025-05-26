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

# Activer Apache mod_rewrite
RUN a2enmod rewrite

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Copier tous les fichiers
COPY . .

# Nettoyer les dump() des templates Twig pour la production
RUN find /var/www/html/templates -name "*.twig" -type f -exec sed -i 's/{{ *dump([^}]*) *}}/<!-- dump removed for production -->/g' {} + || true

# Configuration Apache simple
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Créer les dossiers nécessaires
RUN mkdir -p var/cache var/log

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/var

# Script de démarrage ultra simple
RUN echo '#!/bin/bash\n\
echo "Démarrage de l'\''application..."\n\
\n\
# Nettoyer le cache si possible\n\
php bin/console cache:clear --env=prod --no-debug 2>/dev/null || echo "Cache clear failed, continuing..."\n\
\n\
# Permissions\n\
chown -R www-data:www-data /var/www/html/var\n\
\n\
echo "Starting Apache..."\n\
apache2-foreground\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]