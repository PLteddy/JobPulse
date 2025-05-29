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

# Activer Apache mod_rewrite et headers
RUN a2enmod rewrite headers

# Installer Composer avec vérification
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer --version

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier composer.json et composer.lock en premier pour optimiser le cache Docker
COPY composer.json composer.lock* ./

# Installation des dépendances Composer
RUN set -ex && \
    COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --ignore-platform-reqs && \
    composer clear-cache

# Copier tous les autres fichiers du projet
COPY . .

# Configurer Apache avec une configuration plus robuste
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    ServerName localhost\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes\n\
        FallbackResource /index.php\n\
        <IfModule mod_rewrite.c>\n\
            RewriteEngine On\n\
            RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$\n\
            RewriteRule .* - [E=BASE:%1]\n\
            RewriteCond %{HTTP:Authorization} .\n\
            RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\
            RewriteCond %{ENV:REDIRECT_STATUS} =""\n\
            RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]\n\
            RewriteCond %{REQUEST_FILENAME} !-f\n\
            RewriteRule ^ %{ENV:BASE}/index.php [L]\n\
        </IfModule>\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel warn\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Créer les répertoires nécessaires
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions

# Créer un .htaccess robuste dans public/
RUN echo 'DirectoryIndex index.php\n\
<IfModule mod_negotiation.c>\n\
    Options -MultiViews\n\
</IfModule>\n\
\n\
<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$\n\
    RewriteRule .* - [E=BASE:%1]\n\
    RewriteCond %{HTTP:Authorization} .\n\
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\
    RewriteCond %{ENV:REDIRECT_STATUS} =""\n\
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^ %{ENV:BASE}/index.php [L]\n\
</IfModule>\n\
\n\
<IfModule !mod_rewrite.c>\n\
    <IfModule mod_alias.c>\n\
        RedirectMatch 307 ^/$ /index.php/\n\
    </IfModule>\n\
</IfModule>' > /var/www/html/public/.htaccess

# Script d'entrée amélioré avec plus de diagnostics
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== DIAGNOSTIC JOBPULSE DÉTAILLÉ ==="\n\
echo "Date: $(date)"\n\
echo "PHP Version: $(php -v | head -n 1)"\n\
echo "Composer Version: $(composer --version)"\n\
echo "Working Directory: $(pwd)"\n\
echo "User: $(whoami)"\n\
\n\
# Vérifications critiques\n\
echo "=== VÉRIFICATION STRUCTURE ==="\n\
if [ ! -f "public/index.php" ]; then\n\
    echo "ERREUR CRITIQUE: public/index.php non trouvé!"\n\
    echo "Contenu du répertoire public:"\n\
    ls -la public/ 2>/dev/null || echo "Répertoire public non trouvé"\n\
    echo "Structure du projet:"\n\
    find . -maxdepth 3 -name "*.php" | head -20\n\
    exit 1\n\
fi\n\
\n\
if [ ! -d "vendor" ]; then\n\
    echo "ERREUR CRITIQUE: Dossier vendor non trouvé!"\n\
    echo "Réinstallation de Composer..."\n\
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction\n\
fi\n\
\n\
# Test de syntaxe PHP\n\
echo "=== TEST SYNTAXE PHP ==="\n\
php -l public/index.php || exit 1\n\
\n\
# Configuration Symfony\n\
echo "=== CONFIGURATION SYMFONY ==="\n\
export APP_ENV=${APP_ENV:-prod}\n\
export APP_DEBUG=${APP_DEBUG:-0}\n\
echo "APP_ENV=$APP_ENV"\n\
echo "APP_DEBUG=$APP_DEBUG"\n\
\n\
# Base de données (optionnel)\n\
if [ -n "$DATABASE_URL" ]; then\n\
    echo "=== CONFIGURATION BASE DE DONNÉES ==="\n\
    echo "DATABASE_URL configurée"\n\
    # Tentative de création/migration avec gestion d erreurs\n\
    php bin/console doctrine:database:create --if-not-exists -n 2>/dev/null || echo "DB creation skipped"\n\
    php bin/console doctrine:migrations:migrate -n --allow-no-migration 2>/dev/null || echo "Migration skipped"\n\
else\n\
    echo "ATTENTION: DATABASE_URL non configurée (optionnel)"\n\
fi\n\
\n\
# Cache Symfony\n\
echo "=== CACHE SYMFONY ==="\n\
# Nettoyage du cache avec gestion d erreurs\n\
php bin/console cache:clear --env=prod --no-debug 2>/dev/null || {\n\
    echo "Cache clear failed, suppression manuelle..."\n\
    rm -rf var/cache/* 2>/dev/null || true\n\
}\n\
\n\
# Warmup du cache\n\
php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || echo "Cache warmup failed (non critique)"\n\
\n\
# Permissions finales\n\
echo "=== PERMISSIONS ==="\n\
chown -R www-data:www-data /var/www/html/var 2>/dev/null || true\n\
chmod -R 775 /var/www/html/var 2>/dev/null || true\n\
chmod 644 /var/www/html/public/.htaccess 2>/dev/null || true\n\
\n\
# Test final\n\
echo "=== TEST FINAL ==="\n\
echo "Symfony console disponible: $(php bin/console --version 2>/dev/null || echo Non)"\n\
echo "Routes principales:"\n\
php bin/console debug:router 2>/dev/null | head -10 || echo "Impossible de lister les routes"\n\
\n\
echo "=== DÉMARRAGE APACHE ==="\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Configuration finale des permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && chmod -R 775 /var/www/html/var 2>/dev/null || true

# Variables d'environnement
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Exposer le port 80
EXPOSE 80

# Point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]