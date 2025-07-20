FROM php:8.2-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    dcron \
    mariadb-client \
    curl \
    wget \
    bash \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    && rm -rf /var/cache/apk/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
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

# Copy crontab
COPY docker/cron/crontab /etc/crontabs/root

# Create necessary directories and files
RUN mkdir -p /var/log/cron \
    && mkdir -p /var/www/html/logs \
    && touch /var/log/cron.log \
    && chmod 600 /etc/crontabs/root

# Copy startup script
COPY docker/cron/start-cron.sh /usr/local/bin/start-cron.sh
RUN chmod +x /usr/local/bin/start-cron.sh

# Create a simple health check script
RUN echo '#!/bin/sh' > /usr/local/bin/health-check.sh \
    && echo 'if pgrep crond > /dev/null; then' >> /usr/local/bin/health-check.sh \
    && echo '  exit 0' >> /usr/local/bin/health-check.sh \
    && echo 'else' >> /usr/local/bin/health-check.sh \
    && echo '  exit 1' >> /usr/local/bin/health-check.sh \
    && echo 'fi' >> /usr/local/bin/health-check.sh \
    && chmod +x /usr/local/bin/health-check.sh

# Set proper permissions
RUN chmod 755 /var/www/html \
    && chmod 755 /var/www/html/logs

# Use a different approach - run as root and disable setpgid
USER root

# Command to run
ENTRYPOINT ["/usr/local/bin/start-cron.sh"] 