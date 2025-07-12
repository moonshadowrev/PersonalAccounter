#!/bin/bash
set -e

echo "Starting Accounting Panel Cron Service..."

# Wait for database to be ready
echo "Waiting for database connection..."
until php -r "
try {
    \$pdo = new PDO('mysql:host=\$_ENV[DB_HOST];dbname=\$_ENV[DB_NAME]', \$_ENV[DB_USER], \$_ENV[DB_PASS]);
    echo 'Database connected successfully';
    exit(0);
} catch (PDOException \$e) {
    echo 'Database connection failed: ' . \$e->getMessage();
    exit(1);
}
"; do
    echo "Database not ready, waiting 5 seconds..."
    sleep 5
done

# Ensure log directory exists
mkdir -p /var/www/html/logs

# Create initial log files
touch /var/www/html/logs/cron.log
touch /var/www/html/logs/health.log

# Set proper permissions
chown -R root:root /var/www/html/logs
chmod 755 /var/www/html/logs
chmod 644 /var/www/html/logs/*.log

# Start cron service
echo "Starting cron daemon..."
service cron start

# Check if control script exists and is executable
if [ -f "/var/www/html/control" ]; then
    chmod +x /var/www/html/control
    echo "Control script is ready"
else
    echo "Warning: Control script not found"
fi

# Initial health check
echo "$(date): Cron service started successfully" >> /var/www/html/logs/health.log

# Keep the container running and tail the logs
echo "Cron service is running. Tailing logs..."
tail -f /var/www/html/logs/cron.log /var/www/html/logs/health.log /var/log/cron.log 2>/dev/null &

# Wait for cron service to stop
wait 