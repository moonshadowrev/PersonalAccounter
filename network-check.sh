#!/bin/bash

# PersonalAccounter Docker Network Compatibility Checker
# This script helps diagnose and resolve Docker network conflicts

set -e

# Colors for output
readonly GREEN='\033[0;32m'
readonly BLUE='\033[0;34m'
readonly YELLOW='\033[1;33m'
readonly RED='\033[0;31m'
readonly NC='\033[0m' # No Color

print_header() {
    echo -e "${BLUE}╭─────────────────────────────────────────────────────────╮${NC}"
    echo -e "${BLUE}│          PersonalAccounter Docker Network Checker       │${NC}"
    echo -e "${BLUE}╰─────────────────────────────────────────────────────────╯${NC}"
    echo ""
}

check_network_conflicts() {
    echo -e "${BLUE}🔍 Checking for network conflicts...${NC}"
    
    # Get current subnet from .env
    CURRENT_SUBNET=$(grep "DOCKER_SUBNET=" .env 2>/dev/null | cut -d'=' -f2 || echo "172.28.0.0/24")
    echo -e "${YELLOW}Current configured subnet: ${CURRENT_SUBNET}${NC}"
    
    # Check if subnet is in use (cross-platform)
    if command -v ip >/dev/null 2>&1; then
        # Linux
        if ip route | grep -q "${CURRENT_SUBNET%/*}"; then
            echo -e "${RED}⚠️  Warning: Subnet ${CURRENT_SUBNET} might conflict with existing routes${NC}"
            return 1
        fi
    elif command -v netstat >/dev/null 2>&1; then
        # macOS/BSD
        if netstat -rn | grep -q "${CURRENT_SUBNET%/*}"; then
            echo -e "${RED}⚠️  Warning: Subnet ${CURRENT_SUBNET} might conflict with existing routes${NC}"
            return 1
        fi
    else
        echo -e "${YELLOW}💡 Cannot check route conflicts on this system${NC}"
    fi
    
    # Check Docker networks
    if docker network ls --format "table {{.Name}}\t{{.Driver}}\t{{.Scope}}" | grep -q "accounting"; then
        echo -e "${YELLOW}📍 Found existing accounting networks:${NC}"
        docker network ls | grep accounting
        echo ""
    fi
    
    echo -e "${GREEN}✅ No obvious conflicts detected${NC}"
    return 0
}

suggest_alternatives() {
    echo -e "${BLUE}🛠️  Network Configuration Options:${NC}"
    echo ""
    echo -e "${GREEN}1. Auto-Managed (Recommended):${NC}"
    echo "   • Let Docker choose subnet automatically"
    echo "   • Automatic service discovery (database, app, etc.)"
    echo "   • Best for: Most deployments, avoiding conflicts"
    echo "   • Current status: $(if grep -q 'name:' docker-compose.yml 2>/dev/null; then echo 'Not active'; else echo 'Active'; fi)"
    echo ""
    echo -e "${GREEN}2. Custom Subnet (Advanced):${NC}"
    echo "   • Subnet: ${DOCKER_SUBNET:-172.28.0.0/24}"
    echo "   • Gateway: ${DOCKER_GATEWAY:-172.28.0.1}"
    echo "   • Good for: Specific network requirements"
    echo "   • Requires manual configuration"
    echo ""
    echo -e "${GREEN}3. Alternative Subnets (if conflicts persist):${NC}"
    echo "   • 172.29.0.0/24 (less common)"
    echo "   • 172.30.0.0/24 (even less common)"
    echo "   • 10.99.0.0/24 (private class A)"
    echo "   • 192.168.99.0/24 (private class C)"
}

fix_conflicts() {
    echo -e "${BLUE}🔧 Fixing network conflicts...${NC}"
    
    # Stop containers if running
    if docker compose ps -q > /dev/null 2>&1; then
        echo -e "${YELLOW}Stopping containers...${NC}"
        docker compose down
    fi
    
    # Remove existing network if it exists
    if docker network ls | grep -q "accounting"; then
        echo -e "${YELLOW}Removing existing networks...${NC}"
        docker network ls --format "{{.Name}}" | grep accounting | xargs -r docker network rm 2>/dev/null || true
    fi
    
    echo -e "${GREEN}✅ Networks cleaned up${NC}"
}

switch_to_auto_managed() {
    echo -e "${BLUE}🔄 Ensuring auto-managed network configuration...${NC}"
    
    # Create backup
    cp docker-compose.yml docker-compose.yml.backup
    
    # Check if we're already using the simple configuration
    if grep -q "name: accounting_panel_network" docker-compose.yml; then
        echo -e "${YELLOW}Removing custom network name for better service discovery...${NC}"
        sed -i.tmp '/name: accounting_panel_network/d' docker-compose.yml
        rm docker-compose.yml.tmp 2>/dev/null || true
        echo -e "${GREEN}✅ Updated to auto-managed network${NC}"
    else
        echo -e "${GREEN}✅ Already using auto-managed network${NC}"
    fi
    
    echo -e "${YELLOW}💡 Backup saved as docker-compose.yml.backup${NC}"
}

change_subnet() {
    local new_subnet="$1"
    local new_gateway="$2"
    
    echo -e "${BLUE}🔄 Changing subnet to ${new_subnet}...${NC}"
    
    # Update .env file
    if grep -q "DOCKER_SUBNET=" .env; then
        sed -i.tmp "s/DOCKER_SUBNET=.*/DOCKER_SUBNET=${new_subnet}/" .env
    else
        echo "DOCKER_SUBNET=${new_subnet}" >> .env
    fi
    
    if grep -q "DOCKER_GATEWAY=" .env; then
        sed -i.tmp "s/DOCKER_GATEWAY=.*/DOCKER_GATEWAY=${new_gateway}/" .env
    else
        echo "DOCKER_GATEWAY=${new_gateway}" >> .env
    fi
    
    rm .env.tmp 2>/dev/null || true
    
    echo -e "${GREEN}✅ Subnet updated in .env file${NC}"
}

main() {
    print_header
    
    case "${1:-check}" in
        "check")
            check_network_conflicts
            ;;
        "suggest")
            suggest_alternatives
            ;;
        "fix")
            fix_conflicts
            ;;
        "auto")
            fix_conflicts
            switch_to_auto_managed
            echo -e "${GREEN}🚀 Ready to start with auto-managed networks!${NC}"
            echo -e "${BLUE}Run: docker compose up -d${NC}"
            ;;
        "subnet")
            if [ -z "$2" ] || [ -z "$3" ]; then
                echo -e "${RED}Usage: $0 subnet <subnet> <gateway>${NC}"
                echo -e "${YELLOW}Example: $0 subnet 172.29.0.0/24 172.29.0.1${NC}"
                exit 1
            fi
            fix_conflicts
            change_subnet "$2" "$3"
            echo -e "${GREEN}🚀 Ready to start with new subnet!${NC}"
            echo -e "${BLUE}Run: docker compose up -d${NC}"
            ;;
        "help"|*)
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  check     - Check for network conflicts (default)"
            echo "  suggest   - Show network configuration options"
            echo "  fix       - Clean up existing networks"
            echo "  auto      - Switch to auto-managed networks (maximum compatibility)"
            echo "  subnet    - Change to custom subnet: $0 subnet 172.29.0.0/24 172.29.0.1"
            echo "  help      - Show this help"
            ;;
    esac
}

main "$@" 