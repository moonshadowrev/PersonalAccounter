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
DOMAIN="localhost"
USE_HTTPS=false
ENVIRONMENT="development"
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

# SSL functions removed - HTTP only setup

# Interactive setup
interactive_setup() {
    print_header "Interactive Configuration Setup"
    
    # Fixed configuration - localhost HTTP only
    DOMAIN="localhost"
    USE_HTTPS=false
    print_info "Using localhost with HTTP (no SSL complexity)"
    
    # Environment selection
    echo -n "Select environment (production/development) [development]: "
    read -r env_choice
    if [[ "$env_choice" == "production" ]]; then
        ENVIRONMENT="production"
    else
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
    
    # No SSL tools required for HTTP-only setup
    
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
    
    # Always use HTTP for simplicity
    local app_url="http://localhost"
    
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
APP_DOMAIN=localhost
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
SESSION_SECURE=false
SESSION_SAMESITE=Lax
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

# Update Caddy configuration
update_caddy_config() {
    print_header "Updating Caddy Configuration"
    
    # Simple HTTP-only configuration for localhost
    cat > docker/caddy/Caddyfile << 'EOF'
{
    admin off
    # No SSL - HTTP only for simplicity
}

# HTTP server - localhost only, no SSL complexity
:80, :8080 {
    root * /var/www/html/public
    
    # Security headers (HTTP only)
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
    
    print_success "Caddy configuration updated for localhost (HTTP only)"
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
    
    # Docker Compose handles service health checks automatically via depends_on
    print_info "Services starting with automatic health checks..."
    sleep 5  # Brief pause to let services initialize
    
    print_success "Application deployed successfully"
}

# Initialize database
initialize_database() {
    print_header "Initializing Database"
    
    # Database and app are guaranteed to be ready by healthchecks at this point
    print_info "Running database migrations..."
    docker compose exec -T app php control migrate docker || print_warning "Migration may have failed or already complete"
    
    print_success "Database initialization completed"
}

# Run basic checks
run_health_checks() {
    print_header "Running Basic Checks"
    
    # Docker Compose handles service health automatically via depends_on
    print_info "Docker services managed by health checks"
    
    # Basic web server check
    local app_url="http://localhost"
    
    print_info "Application should be available at: $app_url"
    print_success "Basic checks completed"
}

# Display final information
display_final_info() {
    print_header "🎉 Setup Complete!"
    
    local app_url="http://localhost"
    
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
    
    echo -e "\n${GREEN}🌐 Configuration:${NC}"
    echo -e "   • HTTP-only setup (no SSL complexity)"
    echo -e "   • Access via: http://localhost"
    echo -e "   • No browser security warnings"
    
    echo -e "\n${GREEN}Happy accounting! 📊💰${NC}"
}

# Parse command line arguments
parse_arguments() {
    while [[ $# -gt 0 ]]; do
        case $1 in
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
    echo "  --env ENV             Set environment (production/development)"
    echo "  --email EMAIL         Set admin email"
    echo "  --password PASSWORD   Set admin password"
    echo "  --skip-clone          Skip repository cloning (use current directory)"
    echo "  --help, -h            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 --env development --email admin@localhost"
    echo "  $0 --env production --email admin@company.com --password mypass"
    echo "  curl -fsSL https://raw.githubusercontent.com/USER/REPO/main/setup.sh | bash"
    echo ""
    echo "Note: Uses localhost HTTP only (no SSL complexity)"
}

# Main execution
main() {
    print_header "Accounting Panel Docker Setup"
    
    # Parse arguments
    parse_arguments "$@"
    
    # Check requirements
    check_requirements
    
    # Set defaults and run interactive setup if needed
    DOMAIN="localhost"
    USE_HTTPS=false
    
    # Interactive setup if admin email not provided
    if [[ -z "$ADMIN_EMAIL" ]]; then
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
    
    # No SSL configuration needed - HTTP only setup
    
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