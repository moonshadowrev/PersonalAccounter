# Accounting Panel - Docker Setup

This document provides comprehensive instructions for setting up and running the Accounting Panel using Docker.

## 🚀 Quick Start

### Automated Setup (Recommended)

The easiest way to get started is using the automated setup script:

```bash
# Make the script executable
chmod +x setup.sh

# Run the automated setup
./setup.sh
```

This script will:
- Check and install Docker if needed
- Set up the environment with secure passwords
- Build and deploy all services
- Initialize the database and create an admin user
- Run health checks

### Manual Setup

If you prefer to set up manually:

```bash
# 1. Copy and configure environment
cp .env.example .env
# Edit .env with your preferred settings

# 2. Build and start services
docker-compose up -d --build

# 3. Run database migrations
docker-compose exec app php control migrate run

# 4. Create admin user
docker-compose exec app php control user admin
```

## 📋 Services

The Docker setup includes the following services:

### 🐘 MariaDB Database
- **Container**: `accounting_panel_db`
- **Port**: `3306`
- **Version**: MariaDB 10.11
- **Data**: Persisted in `db_data` volume

### 🐘 PHP Application
- **Container**: `accounting_panel_app`
- **Framework**: PHP 8.2-FPM
- **Extensions**: PDO MySQL, GD, ZIP, OpenSSL, etc.
- **Data**: Logs, sessions, uploads persisted in volumes

### 🌐 Caddy Web Server
- **Container**: `accounting_panel_caddy`
- **Ports**: `80` (HTTP), `443` (HTTPS)
- **Features**: Automatic HTTPS, security headers, compression
- **Configuration**: `docker/caddy/Caddyfile`

### 🔧 phpMyAdmin
- **Container**: `accounting_panel_phpmyadmin`
- **Port**: `8080`
- **Access**: `http://localhost:8080`
- **Credentials**: Use database root credentials

### ⏰ Cron Service
- **Container**: `accounting_panel_cron`
- **Purpose**: Automated scheduled tasks
- **Tasks**: Payment processing, log cleanup, health checks

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost
APP_DOMAIN=localhost

# Database
DB_HOST=database
DB_NAME=accounting_panel
DB_USER=accounting_user
DB_PASS=secure_password
DB_ROOT_PASSWORD=root_password

# Admin User
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@localhost
ADMIN_PASSWORD=admin_password
```

### Service Configuration

#### PHP Configuration
Location: `docker/php/php.ini`

Key settings:
- Memory limit: 256M
- Upload max size: 64M
- Execution time: 300s
- OPcache enabled

#### Caddy Configuration
Location: `docker/caddy/Caddyfile`

Features:
- Automatic HTTPS (in production)
- Security headers
- PHP-FPM integration
- Static file handling
- Access restrictions

#### MariaDB Configuration
Location: `docker/mariadb/init.sql`

Optimizations:
- UTF8MB4 character set
- InnoDB buffer pool sizing
- Connection limits
- Query cache settings

## 🛠️ Management Commands

### Docker Compose Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app

# Rebuild services
docker-compose build --no-cache

# Restart specific service
docker-compose restart app

# Execute commands in containers
docker-compose exec app bash
docker-compose exec database mysql -u root -p
```

### Application Commands

```bash
# Run control commands
docker-compose exec app php control <command>

# Database operations
docker-compose exec app php control migrate run
docker-compose exec app php control db seed
docker-compose exec app php control faker all

# User management
docker-compose exec app php control user create "John Doe" "john@example.com" "password"
docker-compose exec app php control user list

# Schedule operations
docker-compose exec app php control schedule status
docker-compose exec app php control schedule run
```

## 🔄 Docker Swarm Deployment

For production deployments with high availability:

```bash
# Deploy with Docker Swarm
./setup.sh --swarm

# Or manually
docker swarm init
docker build -t accounting_panel:latest .
docker build -t accounting_panel_cron:latest -f docker/cron/Dockerfile .
docker stack deploy -c docker-swarm.yml accounting-panel
```

### Swarm Features

- **Load balancing**: Multiple app replicas
- **High availability**: Automatic failover
- **Resource limits**: CPU and memory constraints
- **Rolling updates**: Zero-downtime deployments
- **Service discovery**: Automatic service networking

### Swarm Management

```bash
# View stack services
docker stack services accounting-panel

# Scale services
docker service scale accounting-panel_app=3

# Update services
docker service update accounting-panel_app

# Remove stack
docker stack rm accounting-panel
```

## 📊 Monitoring and Logs

### Log Locations

- **Application logs**: `logs/` directory
- **Cron logs**: `logs/cron.log`
- **Health checks**: `logs/health.log`
- **Caddy logs**: Available via `docker-compose logs caddy`

### Health Checks

Built-in health checks for:
- Database connectivity
- PHP-FPM process
- Web server response
- Application functionality

```bash
# Check service health
docker-compose ps

# Manual health check
curl -f http://localhost/health

# View health check logs
docker-compose logs caddy | grep health
```

## 🔐 Security

### Default Security Measures

- **Secure passwords**: Generated automatically
- **File permissions**: Proper ownership and modes
- **Network isolation**: Services communicate via private network
- **Access restrictions**: Sensitive files blocked by Caddy
- **Security headers**: XSS protection, CSRF, etc.

### Production Security

For production deployments:

1. **Change default passwords**:
   ```bash
   # Edit .env file
   nano .env
   # Update passwords and restart services
   docker-compose down && docker-compose up -d
   ```

2. **Use HTTPS**:
   ```bash
   # Update APP_URL in .env
   APP_URL=https://your-domain.com
   APP_DOMAIN=your-domain.com
   SESSION_SECURE=true
   ```

3. **Firewall configuration**:
   ```bash
   # Only expose necessary ports
   ufw allow 80,443/tcp
   ufw deny 3306,8080/tcp
   ```

4. **Regular updates**:
   ```bash
   # Update Docker images
   docker-compose pull
   docker-compose up -d
   ```

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Failed
```bash
# Check database status
docker-compose ps database

# Check logs
docker-compose logs database

# Restart database
docker-compose restart database
```

#### Permission Errors
```bash
# Fix permissions
sudo chown -R $USER:$USER .
chmod -R 755 .
chmod -R 777 logs sessions public/uploads
```

#### Port Conflicts
```bash
# Change ports in docker-compose.yml
ports:
  - "8080:80"  # Use different port
  - "8443:443"
```

#### Container Build Issues
```bash
# Clean build
docker-compose down --volumes
docker system prune -a
docker-compose build --no-cache
```

### Debug Mode

Enable debug mode for troubleshooting:

```bash
# Update .env
APP_DEBUG=true
APP_ENV=development

# Restart services
docker-compose restart app
```

## 📚 Additional Resources

### Documentation
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Docker Swarm Documentation](https://docs.docker.com/engine/swarm/)
- [Caddy Documentation](https://caddyserver.com/docs/)
- [MariaDB Documentation](https://mariadb.org/documentation/)

### Support
- Check application logs: `docker-compose logs -f app`
- Review health checks: `curl -v http://localhost`
- Validate configuration: `docker-compose config`

## 🔄 Backup and Recovery

### Database Backup
```bash
# Create backup
docker-compose exec database mysqldump -u root -p${DB_ROOT_PASSWORD} accounting_panel > backup.sql

# Restore backup
docker-compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD} accounting_panel < backup.sql
```

### Full Backup
```bash
# Backup volumes
docker run --rm -v accounting_panel_db_data:/data -v $(pwd):/backup busybox tar czf /backup/db_backup.tar.gz /data
docker run --rm -v accounting_panel_app_uploads:/data -v $(pwd):/backup busybox tar czf /backup/uploads_backup.tar.gz /data
```

### Automated Backups
```bash
# Add to crontab
0 2 * * * cd /path/to/project && docker-compose exec database mysqldump -u root -p${DB_ROOT_PASSWORD} accounting_panel > backups/$(date +%Y%m%d_%H%M%S).sql
```

## 📈 Performance Optimization

### Resource Limits
```yaml
# In docker-compose.yml
deploy:
  resources:
    limits:
      cpus: '2'
      memory: 1G
    reservations:
      cpus: '1'
      memory: 512M
```

### Caching
- OPcache enabled for PHP
- Static file caching via Caddy
- Database query optimization

### Scaling
```bash
# Scale application containers
docker-compose up -d --scale app=3

# Use load balancer
# Configure external load balancer to distribute traffic
```

---

**Happy accounting with Docker! 🐳📊** 