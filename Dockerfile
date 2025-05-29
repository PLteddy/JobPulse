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

# Copier composer.json et composer.lock en premier (pour le cache Docker)
COPY composer.json composer.lock* ./

# Diagnostic initial amélioré
RUN echo "=== DIAGNOSTIC INITIAL ===" && \
    pwd && \
    ls -la && \
    echo "Composer version:" && \
    composer --version && \
    echo "PHP version:" && \
    php --version && \
    echo "Memory limit:" && \
    php -r "echo ini_get('memory_limit');" && \
    echo "" && \
    echo "Composer.json content:" && \
    cat composer.json 2>/dev/null || echo "composer.json NOT FOUND" && \
    echo "=========================="

# Installation Composer avec gestion d'erreur améliorée
RUN if [ -f "composer.json" ]; then \
        echo "Installing Composer dependencies..." && \
        # Augmenter la limite mémoire pour Composer
        php -d memory_limit=512M /usr/bin/composer install \
            --no-dev \
            --optimize-autoloader \
            --no-interaction \
            --verbose \
            --no-cache \
            --ignore-platform-reqs 2>&1 | tee composer-install.log || { \
                echo "=== COMPOSER INSTALL FAILED ==="; \
                echo "Exit code: $?"; \
                echo "Last 20 lines of output:"; \
                tail -20 composer-install.log 2>/dev/null || echo "No log available"; \
                echo "Composer diagnose:"; \
                composer diagnose || true; \
                echo "Available memory:"; \
                free -h || true; \
                echo "Disk space:"; \
                df -h || true; \
                exit 1; \
            }; \
    else \
        echo "No composer.json found, skipping composer install"; \
    fi

# Copier le reste des fichiers après l'installation Composer
COPY . .

# Fix pour éviter les erreurs de bundles dev en production
RUN if [ -f "config/bundles.php" ]; then \
        echo "Checking bundles configuration..." && \
        grep -q "DebugBundle" config/bundles.php && echo "DebugBundle found in config" || echo "DebugBundle not in config"; \
    fi

# Configuration Apache avec ServerName
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

# .htaccess simple
RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteRule ^(.*)$ index.php [QSA,L]' > public/.htaccess

# Script d'entrée avec plus de stabilité
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== STARTING JOBPULSE ==="\n\
echo "Date: $(date)"\n\
echo "PHP Version: $(php -v | head -n 1)"\n\
echo "Working Directory: $(pwd)"\n\
echo "Container User: $(whoami)"\n\
\n\
# Test critique\n\
if [ ! -f "public/index.php" ]; then\n\
    echo "ERROR: public/index.php not found!"\n\
    echo "Available PHP files:"\n\
    find . -name "*.php" -type f | head -10\n\
    exit 1\n\
fi\n\
echo "✓ public/index.php found"\n\
\n\
# Test de syntaxe PHP\n\
php -l public/index.php || exit 1\n\
echo "✓ PHP syntax OK"\n\
\n\
# Configuration Symfony\n\
export APP_ENV=prod\n\
export APP_DEBUG=0\n\
\n\
# Cache Symfony (optionnel)\n\
if [ -f "bin/console" ]; then\n\
    echo "Clearing Symfony cache..."\n\
    php bin/console cache:clear --env=prod --no-debug 2>/dev/null || {\n\
        echo "Cache clear failed, manual cleanup..."\n\
        rm -rf var/cache/* 2>/dev/null || true\n\
    }\n\
    echo "✓ Cache ready"\n\
fi\n\
\n\
# Permissions finales\n\
chown -R www-data:www-data var/ 2>/dev/null || true\n\
chmod -R 775 var/ 2>/dev/null || true\n\
\n\
# Test Apache configuration\n\
apache2ctl configtest || {\n\
    echo "Apache config test failed!"\n\
    exit 1\n\
}\n\
echo "✓ Apache config OK"\n\
\n\
echo "=== STARTING APACHE SERVER ==="\n\
echo "Server will be available on port 80"\n\
\n\
# Trap pour gérer les signaux proprement\n\
trap "echo Stopping...; apache2ctl graceful-stop; exit 0" SIGTERM SIGINT\n\
\n\
exec apache2-foreground\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]