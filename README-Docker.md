# PersonalAccounter v1.0.2 - Docker Deployment Guide

This guide provides comprehensive instructions for deploying PersonalAccounter using Docker in both development and production environments.

## 🆕 What's New in v1.0.2

- **🌐 Enhanced Network Compatibility**: Improved subnet configuration to avoid conflicts
- **🛠️ CLI Management Tools**: New `control-docker` script for easy container management
- **🔧 Network Diagnostics**: `network-check.sh` tool for troubleshooting network issues
- **📊 Adminer Integration**: Lightweight database management interface
- **⚡ Auto-Permission Fixes**: Automatic log directory permission resolution
- **🔧 Auto Docker image build for each version release

## 🚀 Quick Start

### One-Line Installation (Recommended)

The fastest way to get started is using our automated setup script:

```bash
curl -fsSL https://raw.githubusercontent.com/moonshadowrev/PersonalAccounter/main/setup.sh | bash
```

Or using wget:

```bash
wget -qO- https://raw.githubusercontent.com/moonshadowrev/PersonalAccounter/main/setup.sh | bash
```

you can also check out docker built image as well

```bash
docker pull moonshadowrev/personalaccounter
```

This script will:
- Check and install Docker if needed
- Clone the repository
- Configure your domain and security settings
- Generate secure passwords
- Build and deploy all services
- Initialize the database
- Run health checks
- Provide you with login credentials

### Manual Installation

If you prefer to install manually:

```bash
# Clone the repository
git clone https://github.com/moonshadowrev/PersonalAccounter.git
cd PersonalAccounter

# Make the setup script executable
chmod +x setup.sh

# Run the interactive setup
./setup.sh
```

## 🔧 Requirements

Before starting, ensure you have:

- **Docker 20.10+** and **Docker Compose 2.0+**
- **2GB RAM minimum** (4GB recommended for production)
- **1GB free disk space**
- **Linux, macOS, or Windows with WSL2**
- **Port 80 and 443 available** (or configure alternative ports)

## 📋 Services Overview

The Docker setup includes these services:

| Service | Container Name | Port | Description |
|---------|---------------|------|-------------|
| **Web Server** | `accounting_panel_caddy` | 80, 443 | Caddy 2.7 with automatic HTTPS |
| **PHP Application** | `accounting_panel_app` | 9000 | PHP 8.2-FPM with custom framework |
| **Database** | `accounting_panel_db` | 3306 | MariaDB 10.11 with optimizations |
| **Cron Service** | `accounting_panel_cron` | - | Automated background tasks |
| **phpMyAdmin** | `accounting_panel_phpmyadmin` | 8080 | Database management (dev only) |

## 🔐 Security Configuration

The setup script automatically configures:

- **Secure passwords** for all services
- **Domain-specific** session configuration
- **HTTPS support** with automatic SSL certificates
- **CSRF protection** with proper domain binding
- **Security headers** and content policies
- **Rate limiting** on login attempts

### Environment Variables

Key security variables configured automatically:

```bash
# Application Security
APP_ENV=production
APP_DEBUG=false
APP_DOMAIN=your-domain.com
SESSION_SECURE=true
SESSION_SAMESITE=Strict

# Database Security
DB_PASS=randomly-generated-secure-password
DB_ROOT_PASSWORD=randomly-generated-secure-password

# Admin Access
ADMIN_EMAIL=your-email@domain.com
ADMIN_PASSWORD=randomly-generated-secure-password
```

## 🌐 Domain Configuration

### Local Development

For local development, use:

```bash
./setup.sh --domain localhost --env development
```

This configures:
- HTTP-only access at `http://localhost`
- Development tools enabled
- phpMyAdmin accessible at `http://localhost:8080`

### Production Deployment

For production with your domain:

```bash
./setup.sh --domain your-domain.com --https --env production
```

This configures:
- Automatic HTTPS with Let's Encrypt
- Production optimizations
- Security headers enabled
- Development tools disabled

### SSL Certificate Options

The setup script supports multiple SSL certificate options:

#### Let's Encrypt (Recommended for Production)
```bash
./setup.sh --domain your-domain.com --https
```
- Free, automatic SSL certificates
- Automatic renewal
- Trusted by all browsers
- Requires public domain

#### Self-Signed Certificates (Development/Testing)
```bash
./setup.sh --domain dev.local --self-signed
```
- Generated locally during setup
- No external dependencies
- Browsers will show security warnings
- Perfect for development environments

#### No SSL (HTTP Only)
```bash
./setup.sh --domain localhost
```
- HTTP only access
- No encryption
- Only recommended for local development

## 📦 Advanced Configuration

### Custom Ports

If you need to use different ports:

```bash
# Edit .env file
HTTP_PORT=8080
HTTPS_PORT=8443
PHPMYADMIN_PORT=8081
DB_PORT_EXPOSE=3307
```

### Environment Customization

The setup script supports various options:

```bash
# Production setup with Let's Encrypt
./setup.sh --domain example.com --https --email admin@example.com --password secure123

# Development setup with self-signed SSL
./setup.sh --domain dev.local --self-signed --env development --email admin@dev.local

# Local development without SSL
./setup.sh --env development --domain localhost

# Skip repository cloning (run in existing directory)
./setup.sh --skip-clone
```

## 🛠️ Management Commands

### 🆕 New CLI Tools (v1.0.2)

#### control-docker Script
Easy container management without Docker expertise:

```bash
# User management
./control-docker user list
./control-docker user create "John Doe" "john@example.com" "password" "admin"

# Database operations  
./control-docker migrate run
./control-docker db status

# Interactive shell access
./control-docker shell

# Get help
./control-docker help
```

#### network-check.sh Script
Network troubleshooting and configuration:

```bash
# Check for network conflicts
./network-check.sh check

# Show configuration options
./network-check.sh suggest

# Auto-fix network issues (maximum compatibility)
./network-check.sh auto

# Use custom subnet to avoid conflicts
./network-check.sh subnet 172.29.0.0/24 172.29.0.1

# Clean up existing networks
./network-check.sh fix
```

### Service Management

```bash
# View all services
docker compose ps

# View logs
docker compose logs -f

# View specific service logs
docker compose logs -f app
docker compose logs -f database
docker compose logs -f caddy

# Restart services
docker compose restart

# Stop all services
docker compose down

# Start services
docker compose up -d

# Rebuild and restart
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Application Management

```bash
# Access the application container
docker compose exec app bash

# Run database migrations
docker compose exec app php control migrate run

# Create a new admin user
docker compose exec app php control user create "John Doe" "john@example.com" "password"

# View user list
docker compose exec app php control user list

# Run scheduled tasks manually
docker compose exec app php control schedule run

# Check application health
curl http://localhost/health
```

### Database Management

#### 🆕 Adminer Web Interface (v1.0.2)
Access the lightweight database management interface:
- **URL**: http://localhost:8080
- **Server**: `database` (or leave blank)
- **Username**: `accounting_user` or `root`
- **Password**: Check your `.env` file for credentials
- **Database**: `accounting_panel`

#### Command Line Access
```bash
# Access database directly
docker compose exec database mysql -u root -p

# Create database backup
docker compose exec database mysqldump -u root -p accounting_panel > backup.sql

# Restore database backup
docker compose exec -T database mysql -u root -p accounting_panel < backup.sql

# View database logs
docker compose logs database

# 🆕 Start Adminer if not running
docker compose up -d adminer
```

## 🔍 Monitoring and Debugging

### Health Checks

All services include health checks:

```bash
# Check container health
docker compose ps

# Manual health check
curl -f http://localhost/health

# Check service dependencies
docker compose config
```

### Debug Mode

Enable debug mode for troubleshooting:

```bash
# Update .env
APP_DEBUG=true
APP_ENV=development

# Restart application
docker compose restart app
```

### Log Analysis

```bash
# View application logs
docker compose logs -f app

# View error logs
docker compose exec app tail -f logs/app.log

# View access logs
docker compose exec caddy tail -f /var/log/caddy/access.log

# View database logs
docker compose logs database
```

## 🔄 Backup and Recovery

### Automated Backups

```bash
# Add to crontab for daily backups
0 2 * * * cd /path/to/accounting-panel && docker compose exec -T database mysqldump -u root -p${DB_ROOT_PASSWORD} accounting_panel > backups/$(date +%Y%m%d).sql
```

### Manual Backup

```bash
# Create backup directory
mkdir -p backups

# Database backup
docker compose exec database mysqldump -u root -p${DB_ROOT_PASSWORD} accounting_panel > backups/database_$(date +%Y%m%d_%H%M%S).sql

# File uploads backup
docker compose exec app tar -czf - /var/www/html/public/uploads > backups/uploads_$(date +%Y%m%d_%H%M%S).tar.gz

# Configuration backup
cp .env backups/env_$(date +%Y%m%d_%H%M%S).backup
```

### Recovery

```bash
# Restore database
docker compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD} accounting_panel < backups/database_backup.sql

# Restore uploads
docker compose exec -T app tar -xzf - -C /var/www/html/public/uploads < backups/uploads_backup.tar.gz
```

## 📊 Performance Optimization

### Resource Limits

Configure resource limits in `docker-compose.yml`:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 1G
        reservations:
          cpus: '1'
          memory: 512M
```

### Scaling

```bash
# Scale application containers
docker compose up -d --scale app=3

# Use with load balancer
# Configure external load balancer to distribute traffic
```

### Production Optimizations

The setup script automatically applies:

- **OPcache** for PHP performance
- **Database query optimization**
- **Static file caching**
- **Gzip compression**
- **Connection pooling**

## 🐳 Docker Swarm Deployment

For high-availability production deployments:

```bash
# Initialize Docker Swarm
docker swarm init

# Deploy with setup script
./setup.sh --swarm

# Or deploy manually
docker build -t accounting_panel:latest .
docker stack deploy -c docker-swarm.yml accounting-panel
```

### Swarm Management

```bash
# View services
docker stack services accounting-panel

# Scale services
docker service scale accounting-panel_app=3

# Update service
docker service update accounting-panel_app

# Remove stack
docker stack rm accounting-panel
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Docker Build Fails

```bash
# Check Docker daemon
docker info

# Clean Docker cache
docker system prune -a

# Rebuild without cache
docker compose build --no-cache
```

#### 2. Port Conflicts

```bash
# Check port usage
netstat -tulpn | grep :80

# Use different ports
HTTP_PORT=8080 HTTPS_PORT=8443 docker compose up -d
```

#### 🆕 3. Network Conflicts (v1.0.2)

Use the new network diagnostic tool for automatic resolution:

```bash
# Check for network conflicts
./network-check.sh check

# Auto-fix network issues (recommended)
./network-check.sh auto

# Use custom subnet if conflicts persist
./network-check.sh subnet 172.29.0.0/24 172.29.0.1

# Manual network cleanup
./network-check.sh fix
docker compose down
docker compose up -d
```

Common network conflict scenarios:
- **Corporate VPNs**: Often use 172.20.x.x subnets
- **Docker Desktop**: May conflict with default ranges
- **Multiple Docker Projects**: Subnet overlap between projects

#### 4. Database Connection Issues

```bash
# Check database health
docker compose exec database mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1"

# Restart database
docker compose restart database

# Check database logs
docker compose logs database
```

#### 5. Permission Errors

🆕 **v1.0.2 Auto-Fix**: The application now automatically fixes log permission issues on startup.

```bash
# Manual permission fixes (if needed)
sudo chown -R $USER:$USER .
docker compose exec app chown -R www-data:www-data /var/www/html

# 🆕 Restart to trigger auto-fix
docker compose restart app
```

#### 5. CSRF Token Issues

```bash
# Verify domain configuration
grep APP_DOMAIN .env

# Check session settings
grep SESSION_ .env

# Restart application
docker compose restart app
```

### Log Collection

```bash
# Collect all logs for debugging
mkdir -p debug_logs
docker compose logs app > debug_logs/app.log
docker compose logs database > debug_logs/database.log
docker compose logs caddy > debug_logs/caddy.log
docker compose exec app cat logs/app.log > debug_logs/application.log
```

## 🔄 Updates and Maintenance

### Updating the Application

```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker compose build --no-cache

# Update with zero downtime
docker compose up -d --no-deps app
```

### Security Updates

```bash
# Update Docker images
docker compose pull

# Rebuild with latest base images
docker compose build --no-cache --pull

# Restart services
docker compose up -d
```

## 📚 Additional Resources

### Documentation Links

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Docker Swarm Documentation](https://docs.docker.com/engine/swarm/)
- [Caddy Web Server Documentation](https://caddyserver.com/docs/)
- [MariaDB Documentation](https://mariadb.org/documentation/)

### Support

- **Issues**: [GitHub Issues](https://github.com/moonshadowrev/PersonalAccounter/issues)
- **Discussions**: [GitHub Discussions](https://github.com/moonshadowrev/PersonalAccounter/discussions)
- **Wiki**: [Project Wiki](https://github.com/moonshadowrev/PersonalAccounter/wiki)
- **Releases**: [GitHub Releases](https://github.com/moonshadowrev/PersonalAccounter/releases)

### Community

- **Demo**: [Live Demo](https://accounting-panel.overlord.team/login)
- **Documentation**: [Full Documentation](https://moonshadowrev.github.io/PersonalAccounter/)
Use this Credentials for testing:
```bash
E-Mail : admin@example.com
Password : 123456789
```
---

**🎉 Ready to start? Run the one-line installer:**


```bash
curl -fsSL https://raw.githubusercontent.com/moonshadowrev/PersonalAccounter/main/setup.sh | bash
```

**Happy accounting! 📊💰** 
