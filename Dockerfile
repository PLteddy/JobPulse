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
    && docker-php-ext-install intl pdo pdo_mysql zip gmp \
    && rm -rf /var/lib/apt/lists/*

# Activer les modules Apache
RUN a2enmod rewrite headers

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer en premier pour optimiser le cache Docker
COPY composer.json composer.lock ./

# Installer les dépendances Composer
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copier le reste des fichiers de l'application
COPY . .

# Finaliser l'installation de Composer
RUN composer dump-autoload --optimize

# Créer les répertoires var avec les bonnes permissions AVANT de changer d'utilisateur
RUN mkdir -p var/cache/prod/twig/cc var/cache/dev/twig/cc var/log var/sessions \
    && chown -R www-data:www-data . \
    && chmod -R 775 var \
    && chmod -R 777 var/cache

# Fixer le DocumentRoot Apache vers /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

# Configuration Apache pour Symfony
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Copier et rendre exécutable l'entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]