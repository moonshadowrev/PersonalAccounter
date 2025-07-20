#!/bin/bash

# Accounting Panel Docker Setup Script
# Production-ready automated deployment with secure configuration
# 
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/moonshadowrev/PersonalAccounter/main/setup.sh | bash
#   or
#   wget -qO- https://raw.githubusercontent.com/moonshadowrev/PersonalAccounter/main/setup.sh | bash

set -euo pipefail

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Configuration
readonly PROJECT_NAME="PersonalAccounter"
readonly REPO_URL="https://github.com/moonshadowrev/PersonalAccounter.git"
readonly REQUIRED_DOCKER_VERSION="20.10.0"
readonly REQUIRED_COMPOSE_VERSION="2.0.0"

# Global variables
DOMAIN=""
USE_HTTPS=false
SSL_TYPE=""
ENVIRONMENT="production"
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
PROJECT_DIR=""
SKIP_CLONE=false

# Helper functions
print_header() {
    echo -e "\n${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Exit with error message
die() {
    print_error "$1"
    exit 1
}

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Version comparison
version_compare() {
    local version1=$1
    local version2=$2
    printf '%s\n%s\n' "$version1" "$version2" | sort -V | head -n1
}

# Generate secure random password
generate_password() {
    local length=${1:-16}
    if command_exists openssl; then
        openssl rand -base64 32 | tr -d "=+/" | cut -c1-${length}
    elif command_exists /dev/urandom; then
        head -c 32 /dev/urandom | base64 | tr -d "=+/" | cut -c1-${length}
    else
        # Fallback method
        date +%s | sha256sum | base64 | head -c ${length}
    fi
}

# Validate email format
validate_email() {
    local email=$1
    [[ $email =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]
}

# Validate domain format
validate_domain() {
    local domain=$1
    [[ $domain =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$ ]]
}

# Generate self-signed SSL certificates
generate_self_signed_ssl() {
    print_header "Generating Self-Signed SSL Certificates"
    
    local ssl_dir="docker/caddy/ssl"
    mkdir -p "$ssl_dir"
    
    # Generate private key
    print_info "Generating private key..."
    openssl genrsa -out "$ssl_dir/server.key" 4096
    
    # Generate certificate signing request
    print_info "Generating certificate signing request..."
    openssl req -new -key "$ssl_dir/server.key" -out "$ssl_dir/server.csr" -subj "/C=US/ST=State/L=City/O=Organization/OU=Unit/CN=$DOMAIN"
    
    # Generate self-signed certificate (valid for 365 days)
    print_info "Generating self-signed certificate..."
    openssl x509 -req -days 365 -in "$ssl_dir/server.csr" -signkey "$ssl_dir/server.key" -out "$ssl_dir/server.crt"
    
    # Generate certificate with SAN for additional domains
    cat > "$ssl_dir/server.conf" << EOF
[req]
default_bits = 4096
prompt = no
distinguished_name = req_distinguished_name
req_extensions = v3_req

[req_distinguished_name]
C = US
ST = State
L = City
O = Organization
OU = Unit
CN = $DOMAIN

[v3_req]
keyUsage = keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = $DOMAIN
DNS.2 = www.$DOMAIN
DNS.3 = localhost
IP.1 = 127.0.0.1
EOF
    
    # Generate improved certificate with SAN
    openssl req -new -x509 -key "$ssl_dir/server.key" -out "$ssl_dir/server.crt" -days 365 -config "$ssl_dir/server.conf" -extensions v3_req
    
    # Set proper permissions
    chmod 600 "$ssl_dir/server.key"
    chmod 644 "$ssl_dir/server.crt"
    
    # Clean up temporary files
    rm -f "$ssl_dir/server.csr" "$ssl_dir/server.conf"
    
    print_success "Self-signed SSL certificates generated"
    print_warning "Note: Browsers will show a security warning for self-signed certificates"
    print_info "Certificate location: $ssl_dir/"
}

# Interactive setup
interactive_setup() {
    print_header "Interactive Configuration Setup"
    
    # Domain configuration
    while true; do
        echo -n "Enter your domain (e.g., accounting.example.com) or 'localhost' for local setup: "
        read -r DOMAIN
        
        if [[ "$DOMAIN" == "localhost" ]]; then
            USE_HTTPS=false
            break
        elif validate_domain "$DOMAIN"; then
            echo -n "Use HTTPS for $DOMAIN? (y/N): "
            read -r https_choice
            if [[ "$https_choice" =~ ^[Yy]$ ]]; then
                USE_HTTPS=true
                
                # Ask for SSL certificate type
                echo ""
                echo "SSL Certificate Options:"
                echo "1) Let's Encrypt (automatic, free, requires public domain)"
                echo "2) Self-signed (for development/testing)"
                echo -n "Choose SSL type (1-2) [1]: "
                read -r ssl_choice
                
                case $ssl_choice in
                    2)
                        SSL_TYPE="self-signed"
                        print_info "Will generate self-signed SSL certificates"
                        ;;
                    *)
                        SSL_TYPE="letsencrypt"
                        print_info "Will use Let's Encrypt for SSL certificates"
                        ;;
                esac
            fi
            break
        else
            print_error "Invalid domain format. Please try again."
        fi
    done
    
    # Environment selection
    echo -n "Select environment (production/development) [production]: "
    read -r env_choice
    if [[ "$env_choice" == "development" ]]; then
        ENVIRONMENT="development"
    fi
    
    # Admin email
    while true; do
        echo -n "Enter admin email: "
        read -r ADMIN_EMAIL
        
        if validate_email "$ADMIN_EMAIL"; then
            break
        else
            print_error "Invalid email format. Please try again."
        fi
    done
    
    # Admin password
    echo -n "Enter admin password (or press Enter to generate): "
    read -rs password_input
    echo
    
    if [[ -z "$password_input" ]]; then
        ADMIN_PASSWORD=$(generate_password 12)
        print_info "Generated admin password: $ADMIN_PASSWORD"
    else
        ADMIN_PASSWORD="$password_input"
    fi
    
    print_success "Configuration completed"
}

# Check system requirements
check_requirements() {
    print_header "Checking System Requirements"
    
    # Check OS
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        print_success "Linux OS detected"
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        print_success "macOS detected"
    else
        die "Unsupported operating system: $OSTYPE"
    fi
    
    # Check for required tools
    local required_tools=("curl" "git")
    for tool in "${required_tools[@]}"; do
        if command_exists "$tool"; then
            print_success "$tool is available"
        else
            die "$tool is required but not installed"
        fi
    done
    
    # Check for openssl if self-signed certificates are requested
    if [[ "$SSL_TYPE" == "self-signed" ]]; then
        if command_exists openssl; then
            print_success "openssl is available for certificate generation"
        else
            die "openssl is required for self-signed certificate generation but not installed"
        fi
    fi
    
    # Check Docker
    if command_exists docker; then
        local docker_version
        docker_version=$(docker --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n1)
        
        if [[ "$(version_compare "$docker_version" "$REQUIRED_DOCKER_VERSION")" == "$REQUIRED_DOCKER_VERSION" ]]; then
            print_success "Docker $docker_version is compatible"
        else
            die "Docker $REQUIRED_DOCKER_VERSION or higher is required (found: $docker_version)"
        fi
    else
        die "Docker is not installed. Please install Docker first."
    fi
    
    # Check Docker Compose
    if docker compose version >/dev/null 2>&1; then
        local compose_version
        compose_version=$(docker compose version --short)
        print_success "Docker Compose $compose_version is available"
    elif command_exists docker-compose; then
        local compose_version
        compose_version=$(docker-compose --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n1)
        print_success "Docker Compose $compose_version is available"
    else
        die "Docker Compose is not installed"
    fi
    
    # Check Docker daemon
    if ! docker info >/dev/null 2>&1; then
        die "Docker daemon is not running. Please start Docker first."
    fi
    
    print_success "All requirements satisfied"
}

# Clone or update repository
setup_repository() {
    print_header "Setting up Repository"
    
    if [[ "$SKIP_CLONE" == true ]]; then
        PROJECT_DIR="$(pwd)"
        print_info "Using current directory: $PROJECT_DIR"
        return
    fi
    
    PROJECT_DIR="$(pwd)/$PROJECT_NAME"
    
    if [[ -d "$PROJECT_DIR" ]]; then
        print_info "Directory exists, updating repository..."
        cd "$PROJECT_DIR"
        git pull origin main || die "Failed to update repository"
    else
        print_info "Cloning repository..."
        git clone "$REPO_URL" "$PROJECT_DIR" || die "Failed to clone repository"
        cd "$PROJECT_DIR"
    fi
    
    print_success "Repository setup completed"
}

# Generate environment configuration
generate_environment() {
    print_header "Generating Environment Configuration"
    
    # Generate secure passwords
    local db_root_password=$(generate_password 24)
    local db_user_password=$(generate_password 24)
    local session_key=$(generate_password 32)
    
    # Determine URL scheme
    local app_url
    if [[ "$USE_HTTPS" == true ]]; then
        app_url="https://$DOMAIN"
    else
        app_url="http://$DOMAIN"
    fi
    
    # Copy .env.example if it exists
    if [[ -f ".env.example" ]]; then
        print_info "Copying .env.example to .env..."
        cp .env.example .env
    fi
    
    # Create .env file
    cat > .env << EOF
# Environment Configuration
APP_ENV=$ENVIRONMENT
APP_DEBUG=$([[ "$ENVIRONMENT" == "development" ]] && echo "true" || echo "false")
APP_URL=$app_url
APP_DOMAIN=$DOMAIN
APP_TIMEZONE=UTC

# Database Configuration
DB_HOST=database
DB_NAME=accounting_panel
DB_USER=accounting_user
DB_PASS=$db_user_password
DB_PORT=3306
DB_ROOT_PASSWORD=$db_root_password

# Session Configuration
SESSION_LIFETIME=0
SESSION_SECURE=$([[ "$USE_HTTPS" == true ]] && echo "true" || echo "false")
SESSION_SAMESITE=Strict
SESSION_KEY=$session_key

# Security Configuration
LOGIN_ATTEMPTS_LIMIT=5
LOGIN_ATTEMPTS_TIMEOUT=300

# API Configuration
API_MAX_FAILED_ATTEMPTS=5
API_BLOCK_DURATION=300
API_DEFAULT_RATE_LIMIT=60
API_MAX_RATE_LIMIT=1000

# Logging Configuration
LOG_CHANNEL=file
LOG_LEVEL=$([[ "$ENVIRONMENT" == "development" ]] && echo "debug" || echo "warning")
LOG_MAX_FILES=10

# Admin Configuration
ADMIN_EMAIL=$ADMIN_EMAIL
ADMIN_PASSWORD=$ADMIN_PASSWORD

# Docker Configuration
COMPOSE_PROJECT_NAME=accounting_panel
HTTP_PORT=80
HTTPS_PORT=443
PHPMYADMIN_PORT=8080
DB_PORT_EXPOSE=3306

# Additional Configuration
AUTO_MIGRATE=true
EOF
    
    # Set restrictive permissions
    chmod 600 .env
    
    print_success "Environment configuration generated"
    print_info "Admin credentials: $ADMIN_EMAIL / $ADMIN_PASSWORD"
}

# Setup directory structure
setup_directories() {
    print_header "Setting up Directory Structure"
    
    local directories=(
        "logs"
        "sessions"
        "public/uploads"
        "docker/mariadb"
        "docker/caddy"
        "docker/caddy/ssl"
        "docker/php"
        "docker/cron"
    )
    
    for dir in "${directories[@]}"; do
        mkdir -p "$dir"
        print_success "Created directory: $dir"
    done
    
    # Set permissions
    chmod 755 logs sessions public/uploads
    
    print_success "Directory structure setup completed"
}

# Update Caddy configuration for domain
update_caddy_config() {
    print_header "Updating Caddy Configuration"
    
    if [[ "$DOMAIN" == "localhost" ]]; then
        # Localhost configuration - HTTP only, port-based
        cat > docker/caddy/Caddyfile << 'EOF'
{
    admin off
}

# HTTP server for localhost - ports 80 and 8080
:80, :8080 {
    root * /var/www/html/public
    
    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        -Server
    }
    
    # Enable file server
    file_server
    
    # Enable gzip compression  
    encode gzip
    
    # PHP handling
    php_fastcgi app:9000
}
EOF
    else
        # Domain-based configuration
        if [[ "$SSL_TYPE" == "letsencrypt" ]]; then
            # Let's Encrypt configuration - automatic HTTPS
            cat > docker/caddy/Caddyfile << EOF
{
    admin off
    email ${ADMIN_EMAIL}
}

# HTTP and HTTPS for ${DOMAIN}
${DOMAIN} {
    root * /var/www/html/public
    
    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        -Server
    }
    
    # Enable file server
    file_server
    
    # Enable gzip compression
    encode gzip
    
    # PHP handling
    php_fastcgi app:9000
}
EOF
        else
            # Self-signed certificate configuration
            cat > docker/caddy/Caddyfile << EOF
{
    admin off
    local_certs
}

# HTTP server
:80, :8080 {
    root * /var/www/html/public
    
    # Security headers
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        -Server
    }
    
    # Enable file server
    file_server
    
    # Enable gzip compression
    encode gzip
    
    # PHP handling
    php_fastcgi app:9000
}

# HTTPS server with self-signed certificates
:443, :8443 {
    root * /var/www/html/public
    
    # Self-signed TLS certificate
    tls internal
    
    # Security headers  
    header {
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        -Server
    }
    
    # Enable file server
    file_server
    
    # Enable gzip compression
    encode gzip
    
    # PHP handling
    php_fastcgi app:9000
}
EOF
        fi
    fi
    
    print_success "Caddy configuration updated for $DOMAIN with SSL type: ${SSL_TYPE:-none}"
}

# Build and deploy application
deploy_application() {
    print_header "Building and Deploying Application"
    
    # Stop existing containers
    print_info "Stopping existing containers..."
    docker compose down --remove-orphans 2>/dev/null || true
    
    # Build the application
    print_info "Building application containers..."
    docker compose build --no-cache
    
    # Start the services
    print_info "Starting services..."
    if [[ "$ENVIRONMENT" == "development" ]]; then
        docker compose --profile development up -d
    else
        docker compose up -d
    fi
    
    # Wait for services to be healthy
    print_info "Waiting for services to be healthy..."
    local max_wait=300
    local count=0
    
    while true; do
        # Check health status using multiple methods for compatibility
        local health_status=""
        if command -v jq >/dev/null 2>&1; then
            # Try different health check formats
            health_status=$(docker compose ps --format json 2>/dev/null | jq -r '.[].State // .[].Status // "unknown"' 2>/dev/null | grep -c "running\|healthy" || echo "0")
        else
            # Fallback without jq - just check if containers are running
            health_status=$(docker compose ps --format "table {{.State}}" 2>/dev/null | grep -c "running" || echo "0")
        fi
        
        # Check if all expected services are healthy/running (at least 4: app, database, caddy, cron)
        if [[ "$health_status" -ge 4 ]]; then
            break
        fi
        
        if [[ $count -ge $max_wait ]]; then
            die "Services failed to become healthy within $max_wait seconds"
        fi
        sleep 2
        ((count+=2))
    done
    
    print_success "Application deployed successfully"
}

# Initialize database
initialize_database() {
    print_header "Initializing Database"
    
    # Get variables from .env file if they're not already set
    if [[ -f ".env" ]]; then
        source .env
    fi
    
    # Wait for database to be ready
    print_info "Waiting for database to be ready..."
    local max_wait=120
    local count=0
    
    while ! docker compose exec -T database mariadb -u root -p"$DB_ROOT_PASSWORD" --skip-ssl -e "SELECT 1" >/dev/null 2>&1; do
        if [[ $count -ge $max_wait ]]; then
            die "Database failed to start within $max_wait seconds"
        fi
        sleep 2
        ((count+=2))
    done
    
    # Run database migrations
    print_info "Running database migrations..."
    docker compose exec -T app php control migrate docker || print_warning "Migration may have failed or already complete"
    
    print_success "Database initialization completed"
}

# Run health checks
run_health_checks() {
    print_header "Running Health Checks"
    
    # Check container health  
    local running_services=0
    if command -v jq >/dev/null 2>&1; then
        running_services=$(docker compose ps --format json 2>/dev/null | jq -r '.[].State // .[].Status // "unknown"' 2>/dev/null | grep -c "running\|healthy" || echo "0")
    else
        running_services=$(docker compose ps --format "table {{.State}}" 2>/dev/null | grep -c "running" || echo "0")
    fi
    
    if [[ "$running_services" -ge 4 ]]; then
        print_success "All containers are healthy ($running_services services running)"
    else
        print_warning "Some containers may not be healthy ($running_services services running)"
    fi
    
    # Check web server
    local app_url
    if [[ "$USE_HTTPS" == true ]]; then
        app_url="https://$DOMAIN"
    else
        app_url="http://$DOMAIN"
    fi
    
    if [[ "$DOMAIN" == "localhost" ]]; then
        app_url="http://localhost"
    fi
    
    print_info "Checking web server at $app_url..."
    if curl -f -s "$app_url/health" >/dev/null 2>&1; then
        print_success "Web server is responding"
    else
        print_warning "Web server may not be ready yet"
    fi
    
    print_success "Health checks completed"
}

# Display final information
display_final_info() {
    print_header "🎉 Setup Complete!"
    
    local app_url
    if [[ "$USE_HTTPS" == true ]]; then
        app_url="https://$DOMAIN"
    else
        app_url="http://$DOMAIN"
    fi
    
    if [[ "$DOMAIN" == "localhost" ]]; then
        app_url="http://localhost"
    fi
    
    echo -e "${GREEN}Accounting Panel has been successfully deployed!${NC}\n"
    
    echo -e "${BLUE}📋 Service Information:${NC}"
    echo -e "   • Main Application: ${GREEN}$app_url${NC}"
    if [[ "$ENVIRONMENT" == "development" ]]; then
        echo -e "   • phpMyAdmin: ${GREEN}http://localhost:8080${NC}"
    fi
    echo -e "   • Environment: ${GREEN}$ENVIRONMENT${NC}"
    
    echo -e "\n${BLUE}🔐 Admin Access:${NC}"
    echo -e "   • Email: ${GREEN}$ADMIN_EMAIL${NC}"
    echo -e "   • Password: ${GREEN}$ADMIN_PASSWORD${NC}"
    
    echo -e "\n${BLUE}🛠️ Management Commands:${NC}"
    echo -e "   • View logs: ${GREEN}docker compose logs -f${NC}"
    echo -e "   • Stop services: ${GREEN}docker compose down${NC}"
    echo -e "   • Start services: ${GREEN}docker compose up -d${NC}"
    echo -e "   • Access app container: ${GREEN}docker compose exec app bash${NC}"
    echo -e "   • Run control commands: ${GREEN}docker compose exec app php control <command>${NC}"
    
    echo -e "\n${BLUE}📁 Important Files:${NC}"
    echo -e "   • Environment config: ${GREEN}.env${NC}"
    echo -e "   • Application logs: ${GREEN}logs/app.log${NC}"
    echo -e "   • Project directory: ${GREEN}$PROJECT_DIR${NC}"
    
    echo -e "\n${YELLOW}⚠️ Security Notes:${NC}"
    echo -e "   • Save your admin credentials securely"
    echo -e "   • The .env file contains sensitive information"
    echo -e "   • Regularly update your Docker images"
    echo -e "   • Set up automated backups"
    
    if [[ "$USE_HTTPS" == true ]]; then
        echo -e "\n${GREEN}🔒 SSL Configuration:${NC}"
        if [[ "$SSL_TYPE" == "self-signed" ]]; then
            echo -e "   • Using self-signed SSL certificates"
            echo -e "   • Certificate location: docker/caddy/ssl/"
            echo -e "   • ${YELLOW}Warning: Browsers will show security warnings${NC}"
            echo -e "   • Add security exception or use --https for Let's Encrypt"
        else
            echo -e "   • Using Let's Encrypt SSL certificates"
            echo -e "   • Automatic certificate renewal enabled"
            echo -e "   • No browser warnings expected"
        fi
    elif [[ "$DOMAIN" != "localhost" ]]; then
        echo -e "\n${YELLOW}🔒 HTTPS Recommendation:${NC}"
        echo -e "   • Consider enabling HTTPS for production use"
        echo -e "   • Use --https for Let's Encrypt or --self-signed for development"
    fi
    
    echo -e "\n${GREEN}Happy accounting! 📊💰${NC}"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --domain)
                DOMAIN="$2"
                shift 2
                ;;
            --https)
                USE_HTTPS=true
                SSL_TYPE="letsencrypt"
                shift
                ;;
            --self-signed)
                USE_HTTPS=true
                SSL_TYPE="self-signed"
                shift
                ;;
            --env)
                ENVIRONMENT="$2"
                shift 2
                ;;
            --email)
                ADMIN_EMAIL="$2"
                shift 2
                ;;
            --password)
                ADMIN_PASSWORD="$2"
                shift 2
                ;;
            --skip-clone)
                SKIP_CLONE=true
                shift
                ;;
            --help|-h)
                show_help
                exit 0
                ;;
            *)
                die "Unknown option: $1"
                ;;
        esac
    done
}

# Show help
show_help() {
    echo "Accounting Panel Setup Script"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --domain DOMAIN       Set the domain (default: interactive)"
    echo "  --https               Enable HTTPS with Let's Encrypt (default: false)"
    echo "  --self-signed         Enable HTTPS with self-signed certificates"
    echo "  --env ENV             Set environment (production/development)"
    echo "  --email EMAIL         Set admin email"
    echo "  --password PASSWORD   Set admin password"
    echo "  --skip-clone          Skip repository cloning (use current directory)"
    echo "  --help, -h            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 --domain localhost"
    echo "  $0 --domain example.com --https --email admin@example.com"
    echo "  $0 --domain dev.local --self-signed --email admin@dev.local"
    echo "  curl -fsSL https://raw.githubusercontent.com/USER/REPO/main/setup.sh | bash"
}

# Main execution
main() {
    print_header "Accounting Panel Docker Setup"
    
    # Parse arguments
    parse_arguments "$@"
    
    # Check requirements
    check_requirements
    
    # Interactive setup if not all parameters provided
    if [[ -z "$DOMAIN" || -z "$ADMIN_EMAIL" ]]; then
        interactive_setup
    fi
    
    # Setup repository
    setup_repository
    
    # Generate environment
    generate_environment
    
    # Setup directories
    setup_directories
    
    # Update Caddy configuration
    update_caddy_config
    
    # Generate SSL certificates if needed
    if [[ "$USE_HTTPS" == true && "$SSL_TYPE" == "self-signed" ]]; then
        generate_self_signed_ssl
    fi
    
    # Deploy application
    deploy_application
    
    # Initialize database
    initialize_database
    
    # Run health checks
    run_health_checks
    
    # Display final information
    display_final_info
}

# Check if script is being sourced or executed
if [[ "${BASH_SOURCE[0]:-}" == "${0}" ]] || [[ -z "${BASH_SOURCE[0]:-}" ]]; then
    main "$@"
fi 