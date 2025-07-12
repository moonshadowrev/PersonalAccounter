FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    cron \
    mariadb-client \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    gd \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy cron configuration
COPY docker/cron/crontab /etc/cron.d/accounting-panel-cron

# Set proper permissions for cron
RUN chmod 0644 /etc/cron.d/accounting-panel-cron \
    && crontab /etc/cron.d/accounting-panel-cron

# Create cron log file
RUN touch /var/log/cron.log

# Create the startup script
COPY docker/cron/start-cron.sh /usr/local/bin/start-cron.sh
RUN chmod +x /usr/local/bin/start-cron.sh

# Expose log volume
VOLUME ["/var/www/html/logs"]

# Start cron service
CMD ["/usr/local/bin/start-cron.sh"] 