FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    wget \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    mariadb-client \
    bash \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    && rm -rf /var/cache/apk/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        opcache \
        bcmath \
        exif \
        intl \
        pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create necessary directories
RUN mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/sessions \
    && mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/www/html/vendor

# Copy composer files first for better Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Create PHP-FPM configuration
RUN echo "[www]" > /usr/local/etc/php-fpm.d/www.conf \
    && echo "user = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen = 0.0.0.0:9000" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.process_idle_timeout = 10s" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "decorate_workers_output = no" >> /usr/local/etc/php-fpm.d/www.conf

# Set proper permissions for www-data
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs \
    && chmod -R 777 /var/www/html/sessions \
    && chmod -R 777 /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod +x /var/www/html/control

# Run composer scripts after copying all files
RUN composer dump-autoload --optimize --no-dev

# Create startup script
RUN echo '#!/bin/bash' > /usr/local/bin/start-app.sh \
    && echo 'set -e' >> /usr/local/bin/start-app.sh \
    && echo '' >> /usr/local/bin/start-app.sh \
    && echo '# Wait for database' >> /usr/local/bin/start-app.sh \
    && echo 'echo "Waiting for database connection..."' >> /usr/local/bin/start-app.sh \
    && echo 'until mariadb -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SELECT 1;" >/dev/null 2>&1; do' >> /usr/local/bin/start-app.sh \
    && echo '  echo "Database not ready, waiting 2 seconds..."' >> /usr/local/bin/start-app.sh \
    && echo '  sleep 2' >> /usr/local/bin/start-app.sh \
    && echo 'done' >> /usr/local/bin/start-app.sh \
    && echo 'echo "Database connected successfully!"' >> /usr/local/bin/start-app.sh \
    && echo '' >> /usr/local/bin/start-app.sh \
    && echo '# Ensure proper permissions on critical directories' >> /usr/local/bin/start-app.sh \
    && echo 'echo "Fixing permissions..."' >> /usr/local/bin/start-app.sh \
    && echo 'chown -R www-data:www-data /var/www/html/logs /var/www/html/sessions /var/www/html/public/uploads 2>/dev/null || true' >> /usr/local/bin/start-app.sh \
    && echo 'chmod -R 755 /var/www/html/logs /var/www/html/sessions /var/www/html/public/uploads 2>/dev/null || true' >> /usr/local/bin/start-app.sh \
    && echo '' >> /usr/local/bin/start-app.sh \
    && echo '# Run Docker-specific migrations if control script exists' >> /usr/local/bin/start-app.sh \
    && echo 'if [ -f "/var/www/html/control" ]; then' >> /usr/local/bin/start-app.sh \
    && echo '  echo "Running Docker migrations..."' >> /usr/local/bin/start-app.sh \
    && echo '  cd /var/www/html && php control migrate docker 2>/dev/null || echo "Migrations completed or not needed"' >> /usr/local/bin/start-app.sh \
    && echo 'fi' >> /usr/local/bin/start-app.sh \
    && echo '' >> /usr/local/bin/start-app.sh \
    && echo '# Start PHP-FPM' >> /usr/local/bin/start-app.sh \
    && echo 'echo "Starting PHP-FPM..."' >> /usr/local/bin/start-app.sh \
    && echo 'exec php-fpm' >> /usr/local/bin/start-app.sh \
    && chmod +x /usr/local/bin/start-app.sh

# Expose port 9000
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD pidof php-fpm8.2 || exit 1

# Start the application
CMD ["/usr/local/bin/start-app.sh"] 