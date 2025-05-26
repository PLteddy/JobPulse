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

# Copier tous les fichiers du projet
COPY . .

# Nettoyer et vérifier la structure du projet
RUN ls -la && \
    echo "Contenu de composer.json:" && \
    head -20 composer.json 2>/dev/null || echo "composer.json non trouvé"

# Installation robuste de Composer
RUN set -ex && \
    # Nettoyage initial
    rm -rf vendor/ var/cache/* var/log/* || true && \
    # Vérification et installation
    if [ ! -f "composer.json" ]; then \
        echo "ERREUR: composer.json introuvable!"; \
        exit 1; \
    fi && \
    # Installation des dépendances
    COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-dev \
        --optimize-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --ignore-platform-reqs && \
    # Génération de l'autoloader
    COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev && \
    # Vérification finale
    ls -la vendor/ && \
    echo "Installation Composer réussie"

# Configurer le DocumentRoot d'Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride None\n\
        Require all granted\n\
        FallbackResource /index.php\n\
        <IfModule mod_rewrite.c>\n\
            Options -MultiViews\n\
            RewriteEngine On\n\
            RewriteCond %{REQUEST_FILENAME} !-f\n\
            RewriteRule ^(.*)$ index.php [QSA,L]\n\
        </IfModule>\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    LogLevel warn\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Préparer les répertoires
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log

# Créer un .htaccess de secours
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^(.*)$ index.php [QSA,L]\n\
</IfModule>' > /var/www/html/public/.htaccess

# Script d'entrée avec diagnostic amélioré
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== DIAGNOSTIC JOBPULSE ==="\n\
echo "PHP Version: $(php -v | head -n 1)"\n\
echo "Composer Version: $(composer --version)"\n\
echo "Working Directory: $(pwd)"\n\
echo "Files in root: $(ls -la | head -10)"\n\
echo "Vendor exists: $([ -d vendor ] && echo "YES" || echo "NO")"\n\
\n\
# Vérifications critiques\n\
if [ ! -f "public/index.php" ]; then\n\
    echo "ERREUR CRITIQUE: public/index.php non trouvé!"\n\
    echo "Structure du projet:"\n\
    find . -name "*.php" -o -name "composer.*" | head -20\n\
    exit 1\n\
fi\n\
\n\
if [ ! -d "vendor" ]; then\n\
    echo "ERREUR CRITIQUE: Dossier vendor non trouvé!"\n\
    echo "Réinstallation de Composer..."\n\
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader\n\
fi\n\
\n\
# Configuration base de données\n\
if [ -n "$DATABASE_URL" ]; then\n\
    echo "Base de données configurée, tentative de migration..."\n\
    php bin/console doctrine:database:create --if-not-exists -n 2>/dev/null || echo "DB creation skipped"\n\
    php bin/console doctrine:migrations:migrate -n --allow-no-migration 2>/dev/null || echo "Migration skipped"\n\
else\n\
    echo "ATTENTION: DATABASE_URL non configurée"\n\
fi\n\
\n\
# Préparation Symfony\n\
echo "Nettoyage du cache..."\n\
php bin/console cache:clear --env=prod --no-debug 2>/dev/null || echo "Cache clear failed"\n\
php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || echo "Cache warmup failed"\n\
\n\
# Permissions finales\n\
echo "Configuration des permissions..."\n\
chown -R www-data:www-data /var/www/html/var 2>/dev/null || true\n\
chmod -R 775 /var/www/html/var 2>/dev/null || true\n\
\n\
echo "=== DÉMARRAGE APACHE ==="\n\
exec apache2-foreground\n\
' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Configuration finale des permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && chmod -R 775 /var/www/html/var 2>/dev/null || true

# Variables d'environnement par défaut
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Exposer le port 80
EXPOSE 80

# Point d'entrée
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]