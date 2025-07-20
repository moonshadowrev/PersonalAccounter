#!/bin/bash
set -e

echo "Starting Accounting Panel Cron Service..."

# Load environment variables from .env file
if [[ -f "/var/www/html/.env" ]]; then
    echo "Loading environment variables from .env file..."
    # Export all variables from .env file
    export $(grep -v '^#' /var/www/html/.env | xargs)
else
    echo "Warning: .env file not found!"
fi

# Wait for database to be ready
echo "Waiting for database connection..."
until mariadb -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" --skip-ssl -e "SELECT 1;" >/dev/null 2>&1; do
    echo "Database not ready, waiting 5 seconds..."
    sleep 5
done

echo "Database connected successfully!"

# Ensure log directory exists
mkdir -p /var/www/html/logs
mkdir -p /var/log/cron

# Create initial log files
touch /var/www/html/logs/cron.log
touch /var/www/html/logs/health.log
touch /var/log/cron.log

# Set proper permissions
chmod 755 /var/www/html/logs
chmod 644 /var/www/html/logs/*.log
chmod 644 /var/log/cron.log

# Copy crontab to proper location and set permissions
cp /var/www/html/docker/cron/crontab /var/spool/cron/crontabs/root
chmod 600 /var/spool/cron/crontabs/root

# Initialize cron log
echo "$(date): Cron service started" >> /var/www/html/logs/cron.log

echo "Starting cron daemon..."

# Alternative approach: use a while loop instead of crond to avoid setpgid issues
while true; do
    # Run the cron job manually every minute
    cd /var/www/html && php control schedule cron >> /var/www/html/logs/cron.log 2>&1
    
    # Check for cleanup jobs (weekly on Sunday)
    if [[ $(date +%u) == 7 && $(date +%H:%M) == "02:00" ]]; then
        find /var/www/html/logs -name "*.log" -mtime +7 -delete >> /var/www/html/logs/cron.log 2>&1
    fi
    
    # Health check log (hourly)
    if [[ $(date +%M) == "00" ]]; then
        echo "$(date): System health check" >> /var/www/html/logs/health.log
    fi
    
    # Wait 60 seconds
    sleep 60
done 