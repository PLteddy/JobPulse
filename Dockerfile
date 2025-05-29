FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl pdo pdo_mysql zip gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /var/www/html

# Copy composer files
COPY composer.json ./
# Copy composer.lock only if it exists
COPY composer.loc[k] ./

# DEBUGGING: Show what we copied
RUN echo "=== FILES COPIED ===" && \
    ls -la && \
    echo "=== COMPOSER.JSON CONTENT ===" && \
    cat composer.json && \
    echo "=== END DEBUG ==="

# DEBUGGING: Check Composer and PHP
RUN echo "=== SYSTEM INFO ===" && \
    php -v && \
    composer --version && \
    php -m | grep -E "(curl|openssl|zip|json)" && \
    echo "=== END SYSTEM INFO ==="

# DEBUGGING: Validate composer.json
RUN echo "=== COMPOSER VALIDATION ===" && \
    composer validate --strict --no-check-publish 2>&1 || echo "Validation failed but continuing..."

# DEBUGGING: Check platform requirements
RUN echo "=== PLATFORM REQUIREMENTS ===" && \
    composer check-platform-reqs --no-dev 2>&1 || echo "Platform check failed but continuing with --ignore-platform-reqs"

# DEBUGGING: Show what Composer would do
RUN echo "=== DRY RUN ===" && \
    composer install --dry-run --no-dev --verbose 2>&1 || echo "Dry run failed"

# Try to install with maximum verbosity and error reporting
RUN echo "=== COMPOSER INSTALL ATTEMPT ===" && \
    composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --verbose \
    --profile \
    2>&1 | tee /tmp/composer.log || \
    (echo "=== COMPOSER INSTALL FAILED ===" && \
     echo "=== ERROR LOG ===" && \
     cat /tmp/composer.log && \
     echo "=== TRYING ALTERNATIVE ===" && \
     composer install --no-dev --ignore-platform-reqs --no-scripts --verbose 2>&1 || \
     exit 1)

# Verify installation
RUN echo "=== VERIFICATION ===" && \
    ls -la vendor/ && \
    test -f vendor/autoload.php && \
    echo "✓ Autoloader found" || \
    (echo "✗ Autoloader missing" && exit 1)

# Copy rest of application
COPY . .

# Basic Apache setup
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Create basic structure
RUN mkdir -p public && \
    if [ ! -f public/index.php ]; then \
        echo '<?php require_once "../vendor/autoload.php"; echo "App running!"; ?>' > public/index.php; \
    fi

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]