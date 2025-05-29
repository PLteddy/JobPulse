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

# Configure Apache
RUN a2enmod rewrite headers
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Validate composer.json before installation
RUN composer validate --no-check-publish --no-check-lock || echo "Composer validation warnings (continuing...)"

# Install dependencies with robust error handling
RUN set -e && \
    echo "=== STARTING COMPOSER INSTALL ===" && \
    composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --ignore-platform-reqs \
        --verbose && \
    echo "=== COMPOSER INSTALL COMPLETED ===" && \
    ls -la vendor/ && \
    echo "=== CHECKING AUTOLOADER ===" && \
    test -f vendor/autoload.php && echo "✓ Autoloader found" || (echo "✗ Autoloader missing" && exit 1) && \
    php -r "require 'vendor/autoload.php'; echo 'Autoloader works\n';" && \
    echo "========================"

# Copy application code
COPY . .

# Set up Apache virtual host
COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
        
        # Handle Symfony routing
        <IfModule mod_negotiation.c>
            Options -MultiViews
        </IfModule>
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            
            # Handle Authorization Header
            RewriteCond %{HTTP:Authorization} ^(.+)$
            RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
            
            # Redirect to URI without front controller to prevent duplicate content
            RewriteCond %{ENV:REDIRECT_STATUS} ^$
            RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
            
            # If the requested filename exists, simply serve it.
            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule ^ - [L]
            
            # Rewrite all other queries to the front controller.
            RewriteRule ^ %{ENV:BASE}/index.php [L]
        </IfModule>
    </Directory>
    
    # Disable access to sensitive files
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

# Create necessary directories and set permissions
RUN mkdir -p var/cache var/log public && \
    chown -R www-data:www-data var/ && \
    chmod -R 775 var/

# Create .htaccess as fallback (though the VirtualHost config should handle routing)
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