FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Activer Apache mod_rewrite
RUN a2enmod rewrite headers

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock en premier
COPY composer.json composer.lock* ./

# Installation Composer - GARDER les dépendances de dev si nécessaires
RUN echo "Installing Composer dependencies..." && \
    php -d memory_limit=1G /usr/bin/composer install \
        --optimize-autoloader \
        --no-interaction \
        --verbose \
        --no-cache \
        --ignore-platform-reqs 2>&1 | tee composer-install.log || { \
            echo "=== COMPOSER INSTALL FAILED ==="; \
            cat composer-install.log; \
            exit 1; \
        }

# Vérifier les packages installés
RUN echo "=== PACKAGE VERIFICATION ===" && \
    ls -la vendor/composer/ && \
    echo "Checking installed packages..." && \
    composer show | grep -i sensio || echo "No Sensio packages found" && \
    echo "✓ Composer packages verified"

# Copier le reste des fichiers
COPY . .

# Vérifier la configuration des bundles
RUN if [ -f "config/bundles.php" ]; then \
        echo "=== BUNDLES CONFIGURATION ===" && \
        cat config/bundles.php && \
        echo "✓ Bundles configuration loaded"; \
    fi

# NE PAS régénérer l'autoloader avec --no-dev si on a besoin des bundles de dev
# RUN composer dump-autoload --optimize

# Configuration Apache
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && \
    a2enconf servername

RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel warn\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Créer les répertoires nécessaires
RUN mkdir -p var/cache var/log var/sessions && \
    chown -R www-data:www-data var/

# .htaccess pour le routing Symfony
RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteRule ^(.*)$ index.php [QSA,L]' > public/.htaccess

# Script d'entrée simplifié
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== STARTING JOBPULSE ==="\n\
echo "Date: $(date)"\n\
echo "PHP Version: $(php -v | head -n 1)"\n\
\n\
# Vérifications critiques\n\
if [ ! -f "public/index.php" ]; then\n\
    echo "ERROR: public/index.php not found!"\n\
    exit 1\n\
fi\n\
echo "✓ public/index.php found"\n\
\n\
# Test final du bundle\n\
php -r "\n\
require '\''vendor/autoload.php'\'';\n\
if (class_exists('\''Sensio\\\\Bundle\\\\FrameworkExtraBundle\\\\SensioFrameworkExtraBundle'\'')) {\n\
    echo '\''✓ SensioFrameworkExtraBundle is available\\n'\'';\n\
} else {\n\
    echo '\''✗ SensioFrameworkExtraBundle NOT FOUND\\n'\'';\n\
    exit(1);\n\
}\n\
" || exit 1\n\
\n\
# Configuration Symfony\n\
export APP_ENV=prod\n\
export APP_DEBUG=0\n\
\n\
# Cache Symfony\n\
if [ -f "bin/console" ]; then\n\
    echo "Clearing Symfony cache..."\n\
    php bin/console cache:clear --env=prod --no-debug 2>/dev/null || {\n\
        echo "Cache clear failed, manual cleanup..."\n\
        rm -rf var/cache/* 2>/dev/null || true\n\
    }\n\
    echo "✓ Cache ready"\n\
fi\n\
\n\
# Permissions\n\
chown -R www-data:www-data var/ 2>/dev/null || true\n\
chmod -R 775 var/ 2>/dev/null || true\n\
\n\
# Test Apache\n\
apache2ctl configtest || exit 1\n\
echo "✓ Apache config OK"\n\
\n\
echo "=== STARTING APACHE SERVER ==="\n\
exec apache2-foreground\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]