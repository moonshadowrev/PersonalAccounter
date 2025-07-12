#!/bin/bash

# Accounting Panel Docker Setup Script
# This script automates the complete setup of the accounting panel using Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="accounting-panel"
DOCKER_COMPOSE_VERSION="2.20.0"
REQUIRED_DOCKER_VERSION="20.10.0"

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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to compare versions
version_compare() {
    local version1=$1
    local version2=$2
    if [[ "$(printf '%s\n' "$version1" "$version2" | sort -V | head -n1)" == "$version1" ]]; then
        return 0
    else
        return 1
    fi
}

# Function to check Docker installation
check_docker() {
    print_header "Checking Docker Installation"
    
    if ! command_exists docker; then
        print_error "Docker is not installed"
        print_info "Installing Docker..."
        install_docker
    else
        local docker_version=$(docker --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -n1)
        print_success "Docker is installed (version: $docker_version)"
        
        if version_compare "$docker_version" "$REQUIRED_DOCKER_VERSION"; then
            print_success "Docker version is compatible"
        else
            print_warning "Docker version is older than recommended ($REQUIRED_DOCKER_VERSION)"
            print_info "Consider upgrading Docker for better performance"
        fi
    fi
    
    # Check Docker Compose
    if ! command_exists docker-compose && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose is not installed"
        print_info "Installing Docker Compose..."
        install_docker_compose
    else
        print_success "Docker Compose is available"
    fi
    
    # Check if Docker daemon is running
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker daemon is not running"
        print_info "Starting Docker daemon..."
        start_docker_daemon
    else
        print_success "Docker daemon is running"
    fi
}

# Function to install Docker
install_docker() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Ubuntu/Debian
        if command_exists apt-get; then
            print_info "Installing Docker on Ubuntu/Debian..."
            sudo apt-get update
            sudo apt-get install -y \
                ca-certificates \
                curl \
                gnupg \
                lsb-release
            
            # Add Docker's official GPG key
            sudo mkdir -p /etc/apt/keyrings
            curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
            
            # Add Docker repository
            echo \
                "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
                $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
            
            # Install Docker Engine
            sudo apt-get update
            sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
            
            # Add current user to docker group
            sudo usermod -aG docker $USER
            
        # CentOS/RHEL/Fedora
        elif command_exists yum || command_exists dnf; then
            print_info "Installing Docker on CentOS/RHEL/Fedora..."
            if command_exists dnf; then
                sudo dnf install -y docker docker-compose
            else
                sudo yum install -y docker docker-compose
            fi
            sudo systemctl start docker
            sudo systemctl enable docker
            sudo usermod -aG docker $USER
        else
            print_error "Unsupported Linux distribution"
            print_info "Please install Docker manually: https://docs.docker.com/engine/install/"
            exit 1
        fi
        
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        print_info "Installing Docker on macOS..."
        if command_exists brew; then
            brew install --cask docker
        else
            print_error "Homebrew not found. Please install Docker Desktop manually:"
            print_info "https://docs.docker.com/desktop/install/mac-install/"
            exit 1
        fi
    else
        print_error "Unsupported operating system: $OSTYPE"
        print_info "Please install Docker manually: https://docs.docker.com/engine/install/"
        exit 1
    fi
    
    print_success "Docker installation completed"
    print_warning "Please log out and log back in for Docker permissions to take effect"
}

# Function to install Docker Compose
install_docker_compose() {
    print_info "Installing Docker Compose..."
    
    # Download Docker Compose
    sudo curl -L "https://github.com/docker/compose/releases/download/v${DOCKER_COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    
    # Make it executable
    sudo chmod +x /usr/local/bin/docker-compose
    
    # Create symlink if needed
    if [ ! -f /usr/bin/docker-compose ]; then
        sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose
    fi
    
    print_success "Docker Compose installation completed"
}

# Function to start Docker daemon
start_docker_daemon() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if command_exists systemctl; then
            sudo systemctl start docker
            sudo systemctl enable docker
        else
            sudo service docker start
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        open -a Docker
        print_info "Please wait for Docker Desktop to start..."
        sleep 10
    fi
}

# Function to generate random password
generate_password() {
    local length=${1:-12}
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-${length}
}

# Function to setup environment
setup_environment() {
    print_header "Setting up Environment"
    
    # Generate secure passwords if not already set
    if [ ! -f .env ]; then
        print_info "Creating .env file with secure passwords..."
        
        # Generate secure passwords
        DB_ROOT_PASSWORD=$(generate_password 16)
        DB_USER_PASSWORD=$(generate_password 16)
        ADMIN_PASSWORD=$(generate_password 12)
        
        # Create .env file
        cat > .env << EOF
# Environment Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost
APP_DOMAIN=localhost
APP_TIMEZONE=UTC

# Database Configuration
DB_HOST=database
DB_NAME=accounting_panel
DB_USER=accounting_user
DB_PASS=${DB_USER_PASSWORD}
DB_PORT=3306
DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD}

# Session Configuration
SESSION_LIFETIME=0
SESSION_SECURE=false
SESSION_SAMESITE=Lax

# Authentication Configuration
LOGIN_ATTEMPTS_LIMIT=5
LOGIN_ATTEMPTS_TIMEOUT=300

# API Configuration
API_MAX_FAILED_ATTEMPTS=5
API_BLOCK_DURATION=300
API_DEFAULT_RATE_LIMIT=60
API_MAX_RATE_LIMIT=1000

# Logging Configuration
LOG_CHANNEL=file
LOG_LEVEL=warning
LOG_MAX_FILES=5

# Admin User Configuration (for automated setup)
ADMIN_NAME=Admin
ADMIN_EMAIL=admin@localhost
ADMIN_PASSWORD=${ADMIN_PASSWORD}

# Docker Configuration
COMPOSE_PROJECT_NAME=accounting_panel
COMPOSE_FILE=docker-compose.yml
EOF
        
        print_success ".env file created with secure passwords"
        print_info "Admin credentials: admin@localhost / ${ADMIN_PASSWORD}"
        print_warning "Please save these credentials securely!"
    else
        print_success ".env file already exists"
    fi
    
    # Create necessary directories
    mkdir -p logs sessions public/uploads
    chmod 755 logs sessions public/uploads
    
    print_success "Environment setup completed"
}

# Function to build and deploy
deploy_application() {
    print_header "Building and Deploying Application"
    
    # Stop existing containers
    print_info "Stopping existing containers..."
    docker-compose down --remove-orphans 2>/dev/null || true
    
    # Build the application
    print_info "Building application containers..."
    docker-compose build --no-cache
    
    # Start the services
    print_info "Starting services..."
    docker-compose up -d
    
    # Wait for database to be ready
    print_info "Waiting for database to be ready..."
    timeout 60 bash -c 'until docker-compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1" > /dev/null 2>&1; do sleep 2; done'
    
    print_success "Application containers are running"
}

# Function to initialize database
initialize_database() {
    print_header "Initializing Database"
    
    # Wait a bit more for the application to be ready
    sleep 10
    
    # Run database migrations
    print_info "Running database migrations..."
    docker-compose exec -T app php control migrate run
    
    # Create admin user non-interactively
    print_info "Creating admin user..."
    
    # Source the .env file to get admin credentials
    source .env
    
    # Create admin user using the control command
    docker-compose exec -T app php control user create \
        "${ADMIN_NAME}" \
        "${ADMIN_EMAIL}" \
        "${ADMIN_PASSWORD}" \
        "superadmin"
    
    print_success "Database initialization completed"
}

# Function to run health checks
run_health_checks() {
    print_header "Running Health Checks"
    
    # Check if containers are running
    if docker-compose ps | grep -q "Up"; then
        print_success "Containers are running"
    else
        print_error "Some containers are not running"
        docker-compose ps
        return 1
    fi
    
    # Check web server
    print_info "Checking web server..."
    if curl -f http://localhost >/dev/null 2>&1; then
        print_success "Web server is responding"
    else
        print_warning "Web server is not responding yet (may need more time)"
    fi
    
    # Check database connection
    print_info "Checking database connection..."
    if docker-compose exec -T database mysql -u root -p${DB_ROOT_PASSWORD} -e "SELECT 1" >/dev/null 2>&1; then
        print_success "Database is accessible"
    else
        print_error "Database is not accessible"
        return 1
    fi
    
    # Check phpMyAdmin
    print_info "Checking phpMyAdmin..."
    if curl -f http://localhost:8080 >/dev/null 2>&1; then
        print_success "phpMyAdmin is responding"
    else
        print_warning "phpMyAdmin is not responding yet (may need more time)"
    fi
    
    print_success "Health checks completed"
}

# Function to display final information
display_final_info() {
    print_header "Setup Complete!"
    
    # Source the .env file to get credentials
    source .env
    
    echo -e "${GREEN}🎉 Accounting Panel has been successfully deployed!${NC}\n"
    
    echo -e "${BLUE}📋 Service Information:${NC}"
    echo -e "   • Main Application: ${GREEN}http://localhost${NC}"
    echo -e "   • phpMyAdmin: ${GREEN}http://localhost:8080${NC}"
    echo -e "   • Database Host: ${GREEN}localhost:3306${NC}"
    
    echo -e "\n${BLUE}🔐 Admin Credentials:${NC}"
    echo -e "   • Email: ${GREEN}${ADMIN_EMAIL}${NC}"
    echo -e "   • Password: ${GREEN}${ADMIN_PASSWORD}${NC}"
    
    echo -e "\n${BLUE}🗄️ Database Credentials:${NC}"
    echo -e "   • Database: ${GREEN}${DB_NAME}${NC}"
    echo -e "   • User: ${GREEN}${DB_USER}${NC}"
    echo -e "   • Password: ${GREEN}${DB_PASS}${NC}"
    echo -e "   • Root Password: ${GREEN}${DB_ROOT_PASSWORD}${NC}"
    
    echo -e "\n${BLUE}🛠️ Management Commands:${NC}"
    echo -e "   • View logs: ${GREEN}docker-compose logs -f${NC}"
    echo -e "   • Stop services: ${GREEN}docker-compose down${NC}"
    echo -e "   • Start services: ${GREEN}docker-compose up -d${NC}"
    echo -e "   • Access app container: ${GREEN}docker-compose exec app bash${NC}"
    echo -e "   • Run control commands: ${GREEN}docker-compose exec app php control <command>${NC}"
    
    echo -e "\n${YELLOW}⚠️ Important Notes:${NC}"
    echo -e "   • Please save your admin credentials securely"
    echo -e "   • The .env file contains sensitive information"
    echo -e "   • Consider changing default passwords in production"
    echo -e "   • Backup your data regularly"
    
    echo -e "\n${GREEN}Happy accounting! 📊💰${NC}"
}

# Function to handle Docker Swarm setup
setup_swarm() {
    print_header "Setting up Docker Swarm"
    
    # Check if already in swarm mode
    if docker info --format '{{.Swarm.LocalNodeState}}' | grep -q "active"; then
        print_success "Already in Docker Swarm mode"
    else
        print_info "Initializing Docker Swarm..."
        docker swarm init
        print_success "Docker Swarm initialized"
    fi
    
    # Build images for swarm
    print_info "Building images for swarm deployment..."
    docker build -t accounting_panel:latest .
    docker build -t accounting_panel_cron:latest -f docker/cron/Dockerfile .
    
    # Deploy stack
    print_info "Deploying stack to swarm..."
    docker stack deploy -c docker-swarm.yml accounting-panel
    
    print_success "Stack deployed to Docker Swarm"
    print_info "Use 'docker stack services accounting-panel' to view services"
}

# Main execution
main() {
    print_header "Accounting Panel Docker Setup"
    
    # Check if running as root
    if [[ $EUID -eq 0 ]]; then
        print_warning "Running as root is not recommended for Docker operations"
        print_info "Consider running this script as a regular user"
    fi
    
    # Parse command line arguments
    SWARM_MODE=false
    SKIP_HEALTH_CHECK=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --swarm)
                SWARM_MODE=true
                shift
                ;;
            --skip-health-check)
                SKIP_HEALTH_CHECK=true
                shift
                ;;
            --help|-h)
                echo "Usage: $0 [OPTIONS]"
                echo ""
                echo "Options:"
                echo "  --swarm              Deploy using Docker Swarm"
                echo "  --skip-health-check  Skip health checks"
                echo "  --help, -h           Show this help message"
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    # Check Docker installation
    check_docker
    
    # Setup environment
    setup_environment
    
    # Deploy application
    if [ "$SWARM_MODE" = true ]; then
        setup_swarm
    else
        deploy_application
        
        # Initialize database
        initialize_database
        
        # Run health checks
        if [ "$SKIP_HEALTH_CHECK" = false ]; then
            run_health_checks
        fi
    fi
    
    # Display final information
    display_final_info
}

# Run main function
main "$@" 