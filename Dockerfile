FROM php:8.2-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libzip-dev zip curl \
    && docker-php-ext-install intl pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache
RUN a2enmod rewrite headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies
COPY composer.json composer.lock* ./

# Debug Composer
RUN echo "=== COMPOSER DEBUG ===" && \
    composer --version && \
    echo "Validating composer.json:" && \
    composer validate --no-check-publish || echo "Validation issues found" && \
    echo "Memory limit: $(php -r 'echo ini_get("memory_limit");')" && \
    echo "========================"

# Install with better error handling
RUN php -d memory_limit=1G composer install \
    --optimize-autoloader \
    --no-interaction \
    --ignore-platform-reqs \
    --verbose \
    2>&1 | tee composer-install.log || { \
        echo "=== COMPOSER INSTALL FAILED ==="; \
        echo "Error log:"; \
        cat composer-install.log; \
        echo "Trying without lock file..."; \
        rm -f composer.lock; \
        php -d memory_limit=1G composer install \
            --no-interaction \
            --ignore-platform-reqs \
            --no-cache || exit 1; \
    }

# Code
COPY . .

# Apache config
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Permissions
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var/

# .htaccess
RUN echo 'RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^(.*)$ index.php [QSA,L]' > public/.htaccess

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

EXPOSE 80
CMD ["apache2-foreground"]