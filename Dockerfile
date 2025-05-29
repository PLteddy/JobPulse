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

# Diagnostic initial
RUN echo "=== DIAGNOSTIC COMPOSER ===" && \
    composer --version && \
    echo "Validating composer.json:" && \
    composer validate --no-check-publish || echo "Validation failed" && \
    echo "Composer.json content (first 50 lines):" && \
    head -50 composer.json && \
    echo "=========================="

# Installation Composer AVEC dépendances dev pour les bundles requis
RUN echo "Installing Composer dependencies (including dev for bundles)..." && \
    php -d memory_limit=1G /usr/bin/composer install \
        --optimize-autoloader \
        --no-interaction \
        --verbose \
        --no-cache \
        --ignore-platform-reqs 2>&1 | tee composer-install.log || { \
            echo "=== COMPOSER INSTALL FAILED ==="; \
            echo "Trying to install missing bundle manually..."; \
            composer require sensio/framework-extra-bundle --no-cache --ignore-platform-reqs || true; \
            echo "Retrying full install..."; \
            composer install --optimize-autoloader --no-interaction --ignore-platform-reqs || { \
                echo "Final composer install failed. Logs:"; \
                cat composer-install.log; \
                exit 1; \
            }; \
        }

# Vérification des bundles installés
RUN echo "=== BUNDLES VERIFICATION ===" && \
    echo "Checking for SensioFrameworkExtraBundle:" && \
    find vendor/ -name "*SensioFrameworkExtraBundle*" -type d 2>/dev/null || echo "Bundle not found in vendor/" && \
    echo "Checking autoload files:" && \
    ls -la vendor/composer/ && \
    echo "Autoload classmap check:" && \
    grep -r "SensioFrameworkExtraBundle" vendor/composer/ || echo "Not found in autoload" && \
    echo "=========================="

# Copier le reste des fichiers
COPY . .

# Vérification et correction de la configuration des bundles
RUN if [ -f "config/bundles.php" ]; then \
        echo "=== BUNDLES CONFIGURATION ===" && \
        echo "Current bundles.php content:" && \
        cat config/bundles.php && \
        echo "Checking for SensioFrameworkExtraBundle registration..." && \
        if ! grep -q "SensioFrameworkExtraBundle" config/bundles.php; then \
            echo "Adding SensioFrameworkExtraBundle to bundles.php..."; \
            cp config/bundles.php config/bundles.php.backup; \
            sed -i "/return \[/a\\    Sensio\\\\Bundle\\\\FrameworkExtraBundle\\\\SensioFrameworkExtraBundle::class => ['all' => true]," config/bundles.php; \
        fi; \
        echo "Final bundles.php:"; \
        cat config/bundles.php; \
        echo "========================"; \
    fi

# Regénérer l'autoloader après les modifications
RUN composer dump-autoload --optimize --no-dev

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

# .htaccess
RUN echo 'RewriteEngine On\n\
RewriteCond %{REQUEST_FILENAME} !-f\n\
RewriteRule ^(.*)$ index.php [QSA,L]' > public/.htaccess

# Script d'entrée amélioré avec vérification des bundles
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== STARTING JOBPULSE ==="\n\
echo "Date: $(date)"\n\
echo "PHP Version: $(php -v | head -n 1)"\n\
echo "Working Directory: $(pwd)"\n\
\n\
# Vérifications critiques\n\
if [ ! -f "public/index.php" ]; then\n\
    echo "ERROR: public/index.php not found!"\n\
    exit 1\n\
fi\n\
echo "✓ public/index.php found"\n\
\n\
# Vérification du bundle manquant\n\
echo "Checking SensioFrameworkExtraBundle availability..."\n\
php -r "spl_autoload_register(function(\$class) { include __DIR__ . '\''/vendor/autoload.php\''; }); \n\
if (class_exists('\'Sensio\\\\Bundle\\\\FrameworkExtraBundle\\\\SensioFrameworkExtraBundle\'')) { \n\
    echo '✓ SensioFrameworkExtraBundle is available'; \n\
} else { \n\
    echo '✗ SensioFrameworkExtraBundle NOT FOUND'; \n\
    echo 'Available Sensio classes:'; \n\
    foreach (get_declared_classes() as \$class) { \n\
        if (strpos(\$class, '\'Sensio\'') === 0) echo \$class . PHP_EOL; \n\
    } \n\
}" || echo "Bundle check failed"\n\
\n\
# Test de syntaxe PHP\n\
php -l public/index.php || exit 1\n\
echo "✓ PHP syntax OK"\n\
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
trap "echo Stopping...; apache2ctl graceful-stop; exit 0" SIGTERM SIGINT\n\
exec apache2-foreground\n\
' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]