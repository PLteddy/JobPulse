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

# Copy composer files first
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --verbose

# Copy ALL application files
COPY . .

# Create necessary directories
RUN mkdir -p public var/cache var/log

# Create a basic index.php if it doesn't exist
RUN if [ ! -f public/index.php ]; then \
        echo '<?php' > public/index.php && \
        echo 'echo "<h1>Application is running!</h1>";' >> public/index.php && \
        echo 'echo "<p>PHP Version: " . phpversion() . "</p>";' >> public/index.php && \
        echo 'if (file_exists("../vendor/autoload.php")) {' >> public/index.php && \
        echo '    echo "<p>✓ Composer autoloader found</p>";' >> public/index.php && \
        echo '} else {' >> public/index.php && \
        echo '    echo "<p>✗ Composer autoloader missing</p>";' >> public/index.php && \
        echo '}' >> public/index.php && \
        echo '?>' >> public/index.php; \
    fi

# Fix all permissions BEFORE setting up Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 var/ && \
    chmod 644 public/index.php

# Set up Apache virtual host with proper permissions
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    \n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
        \n\
        <IfModule mod_rewrite.c>\n\
            RewriteEngine On\n\
            RewriteCond %{REQUEST_FILENAME} !-f\n\
            RewriteCond %{REQUEST_FILENAME} !-d\n\
            RewriteRule ^(.*)$ index.php [QSA,L]\n\
        </IfModule>\n\
    </Directory>\n\
    \n\
    <FilesMatch "^\.ht">\n\
        Require all denied\n\
    </FilesMatch>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Add a simple .htaccess for fallback
RUN echo 'DirectoryIndex index.php index.html' > public/.htaccess && \
    echo 'Options +FollowSymLinks' >> public/.htaccess && \
    echo 'RewriteEngine On' >> public/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-f' >> public/.htaccess && \
    echo 'RewriteCond %{REQUEST_FILENAME} !-d' >> public/.htaccess && \
    echo 'RewriteRule ^(.*)$ index.php [QSA,L]' >> public/.htaccess

# Final permission fix
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Debug information
RUN echo "=== DEBUGGING INFO ===" && \
    ls -la /var/www/html/ && \
    echo "=== PUBLIC DIRECTORY ===" && \
    ls -la /var/www/html/public/ && \
    echo "=== PERMISSIONS CHECK ===" && \
    stat /var/www/html/public/index.php && \
    echo "=== APACHE CONFIG CHECK ===" && \
    apache2ctl -t && \
    echo "======================="

EXPOSE 80

# Use exec form to ensure proper signal handling
CMD ["apache2-foreground"]