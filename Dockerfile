FROM php:8.2-apache

# Install system dependencies
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

# Enable Apache modules
RUN a2enmod rewrite headers
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Set working directory
WORKDIR /var/www/html

# Copy only composer files first for caching
COPY composer.json composer.lock* ./

# Debug: Show composer files content
RUN echo "=== COMPOSER.JSON CONTENT ===" && \
    cat composer.json && \
    echo "=== END COMPOSER.JSON ===" && \
    ls -la

# Validate composer.json with more verbose output
RUN composer validate --no-check-publish || echo "Composer validation failed but continuing..."

# Check Composer diagnose
RUN composer diagnose || echo "Composer diagnose completed with warnings"

# Install dependencies with better error reporting
RUN echo "=== STARTING COMPOSER INSTALL ===" && \
    composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --verbose \
    2>&1 | tee /tmp/composer-install.log && \
    echo "=== COMPOSER INSTALL COMPLETED ===" && \
    ls -la vendor/ || (echo "=== COMPOSER INSTALL FAILED ===" && cat /tmp/composer-install.log && exit 1)

# Check if autoloader exists
RUN if [ -f vendor/autoload.php ]; then \
        echo "✓ Autoloader found"; \
    else \
        echo "✗ Autoloader missing"; \
        ls -la vendor/ || echo "vendor directory not found"; \
        exit 1; \
    fi

# Test if autoloader works
RUN php -r "require 'vendor/autoload.php'; echo 'Autoloader works\n';"

# Copy application code
COPY . .

# Set up Apache virtual host
COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted

        <IfModule mod_negotiation.c>
            Options -MultiViews
        </IfModule>

        <IfModule mod_rewrite.c>
            RewriteEngine On

            RewriteCond %{HTTP:Authorization} ^(.+)$
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

            RewriteCond %{ENV:REDIRECT_STATUS} ^$
            RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]

            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule ^ - [L]

            RewriteRule ^ %{ENV:BASE}/index.php [L]
        </IfModule>
    </Directory>

    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    <FilesMatch "\.(yml|yaml|xml)$">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Prepare directories and permissions
RUN mkdir -p var/cache var/log public && \
    chown -R www-data:www-data var/ && \
    chmod -R 775 var/

# Add fallback .htaccess
RUN echo 'DirectoryIndex index.php' > public/.htaccess && \
    echo 'FallbackResource /index.php' >> public/.htaccess

# Warm up cache if Symfony
RUN if [ -f bin/console ]; then \
        php bin/console cache:clear --env=prod --no-debug --no-warmup || true && \
        php bin/console cache:warmup --env=prod --no-debug || true; \
    fi

# Final verification
RUN echo "=== FINAL VERIFICATION ===" && \
    php -v && \
    composer --version && \
    test -f vendor/autoload.php && echo "✓ Autoloader present" && \
    test -f public/index.php && echo "✓ Front controller present" && \
    echo "========================"

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

CMD ["apache2-foreground"]