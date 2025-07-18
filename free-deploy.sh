#!/bin/bash

# Menteego Free Deployment Script for Railway
# Usage: ./free-deploy.sh

set -e

echo "🆓 Menteego FREE Deployment to Railway"
echo "====================================="

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}📋 $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if Node.js is installed (needed for Railway CLI)
if ! command -v npm &> /dev/null; then
    print_error "Node.js/npm is required but not installed."
    echo "Please install Node.js from: https://nodejs.org/"
    exit 1
fi

print_status "Installing Railway CLI..."
npm install -g @railway/cli

print_success "Railway CLI installed"

print_status "Preparing project for Railway deployment..."

# Create/update composer.json with required dependencies
cat > composer.json << 'EOF'
{
    "name": "aces/menteego",
    "description": "ACES Mentor-Mentee Matching Platform",
    "type": "project",
    "require": {
        "php": "^8.1"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "scripts": {
        "post-install-cmd": [
            "php -r \"if (!file_exists('.env')) { copy('.env.example', '.env'); }\""
        ]
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
EOF

# Create .env.example for Railway
cat > .env.railway << 'EOF'
# Railway Environment Variables Template
# Copy these to Railway dashboard -> Variables

DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASS=<MYSQL_PASSWORD from Railway>

APP_ENV=production
APP_DEBUG=false
APP_URL=<Your Railway App URL>
APP_KEY=<Generate 32-character key>

# Email Configuration (update with your details)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourapp.railway.app
MAIL_FROM_NAME="ACES Menteego"

# File Upload Configuration
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar
UPLOAD_PATH=uploads/

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600
EOF

# Create database import script for Railway
cat > database/railway-import.php << 'EOF'
<?php
// Railway Database Import Script

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Database schema imported successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error importing database: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

print_success "Project prepared for Railway"

print_status "Initializing Railway project..."

# Check if already logged in
if ! railway whoami &> /dev/null; then
    print_warning "Please login to Railway..."
    railway login
fi

print_status "Creating Railway project..."
railway new --name menteego

print_success "Railway project created"

print_status "Adding MySQL database..."
railway add --database mysql

print_warning "Important: Go to railway.app dashboard and:"
echo "1. Click on your project"
echo "2. Click on the MySQL service"
echo "3. Go to 'Variables' tab"
echo "4. Copy the MYSQL_PASSWORD value"
echo ""

read -p "Press Enter after you've noted the MySQL password..."

print_status "Setting up environment variables..."

# Get Railway app URL
APP_URL=$(railway status --json | grep -o '"url":"[^"]*"' | cut -d'"' -f4)
if [ -z "$APP_URL" ]; then
    APP_URL="https://menteego-production.up.railway.app"
fi

# Generate app key
APP_KEY=$(openssl rand -base64 32)

# Set Railway environment variables
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set APP_URL=$APP_URL
railway variables set APP_KEY=$APP_KEY
railway variables set DB_HOST=mysql.railway.internal
railway variables set DB_PORT=3306
railway variables set DB_NAME=railway
railway variables set DB_USER=root

print_warning "Please set the DB_PASS variable manually:"
echo "railway variables set DB_PASS=<your-mysql-password>"
echo ""

print_status "Deploying to Railway..."
railway up

print_success "Application deployed!"

print_status "Importing database schema..."
railway run php database/railway-import.php

print_success "Database schema imported!"

echo ""
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETED! 🎉${NC}"
echo "================================"
echo ""
echo -e "${BLUE}🔗 Your Menteego platform is live at:${NC}"
echo "   $APP_URL"
echo ""
echo -e "${BLUE}📝 Next Steps:${NC}"
echo "   1. Visit your Railway app URL"
echo "   2. Create admin account: $APP_URL/auth/register.php"
echo "   3. Test all functionality"
echo "   4. Update email settings in Railway variables"
echo ""
echo -e "${BLUE}⚙️ To manage your app:${NC}"
echo "   - Railway dashboard: https://railway.app/dashboard"
echo "   - View logs: railway logs"
echo "   - Open shell: railway shell"
echo ""
echo -e "${YELLOW}📧 Email Configuration:${NC}"
echo "   Update these Railway variables with your email settings:"
echo "   - MAIL_USERNAME: your-email@gmail.com"
echo "   - MAIL_PASSWORD: your-app-password"
echo ""
echo -e "${GREEN}✅ Your free Menteego platform is ready for 1000+ users!${NC}"