#!/bin/bash

# Menteego Platform Deployment Script
# Usage: ./deploy.sh [production|staging]

set -e

echo "🚀 Menteego Platform Deployment Script"
echo "======================================"

# Check if environment is specified
if [ $# -eq 0 ]; then
    echo "Usage: ./deploy.sh [production|staging]"
    exit 1
fi

ENVIRONMENT=$1
echo "📋 Deploying to: $ENVIRONMENT"

# Configuration
if [ "$ENVIRONMENT" = "production" ]; then
    DOMAIN="menteego.aces.org"
    BRANCH="main"
elif [ "$ENVIRONMENT" = "staging" ]; then
    DOMAIN="staging.menteego.aces.org"
    BRANCH="develop"
else
    echo "❌ Invalid environment. Use 'production' or 'staging'"
    exit 1
fi

echo "🌐 Domain: $DOMAIN"
echo "🌿 Branch: $BRANCH"

# Pre-deployment checks
echo ""
echo "🔍 Pre-deployment checks..."

# Check if we're in the right directory
if [ ! -f "index.php" ] || [ ! -f "dashboard.php" ]; then
    echo "❌ Not in Menteego project directory"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️  .env file not found. Creating from .env.example..."
    cp .env.example .env
    echo "✅ Please edit .env file with your production settings"
    echo "   Then run this script again."
    exit 1
fi

# Check for required commands
command -v git >/dev/null 2>&1 || { echo "❌ git is required but not installed." >&2; exit 1; }
command -v php >/dev/null 2>&1 || { echo "❌ PHP is required but not installed." >&2; exit 1; }

echo "✅ Pre-deployment checks passed"

# Backup current deployment (if exists)
echo ""
echo "💾 Creating backup..."
BACKUP_DIR="/var/backups/menteego/$(date +%Y%m%d_%H%M%S)"
if [ -d "/var/www/menteego" ]; then
    sudo mkdir -p "$BACKUP_DIR"
    sudo cp -r /var/www/menteego "$BACKUP_DIR/"
    echo "✅ Backup created at: $BACKUP_DIR"
fi

# Deploy application files
echo ""
echo "📁 Deploying application files..."

# Create directory if it doesn't exist
sudo mkdir -p /var/www/menteego

# Copy files (excluding development files)
sudo rsync -av --delete \
    --exclude='.git' \
    --exclude='.env.example' \
    --exclude='PREVIEW_SETUP.md' \
    --exclude='TROUBLESHOOTING.md' \
    --exclude='deploy.sh' \
    --exclude='*.log' \
    ./ /var/www/menteego/

echo "✅ Files deployed"

# Set permissions
echo ""
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data /var/www/menteego
sudo chmod -R 755 /var/www/menteego
sudo chmod -R 777 /var/www/menteego/uploads
sudo chmod 600 /var/www/menteego/.env

echo "✅ Permissions set"

# Database operations
echo ""
echo "🗄️  Database operations..."

# Read database config from .env
DB_NAME=$(grep "^DB_NAME=" /var/www/menteego/.env | cut -d '=' -f2)
DB_USER=$(grep "^DB_USER=" /var/www/menteego/.env | cut -d '=' -f2)
DB_PASS=$(grep "^DB_PASS=" /var/www/menteego/.env | cut -d '=' -f2)

if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
    echo "📊 Importing database schema..."
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/menteego/database/schema.sql
    echo "✅ Database schema imported"
else
    echo "⚠️  Database credentials not found in .env. Skipping database import."
fi

# Apache configuration
echo ""
echo "🔧 Configuring Apache..."

# Create Apache virtual host
sudo tee /etc/apache2/sites-available/menteego.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot /var/www/menteego
    
    <Directory /var/www/menteego>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
    
    ErrorLog \${APACHE_LOG_DIR}/menteego_error.log
    CustomLog \${APACHE_LOG_DIR}/menteego_access.log combined
</VirtualHost>
EOF

# Enable site and required modules
sudo a2ensite menteego.conf
sudo a2enmod rewrite headers deflate expires
sudo systemctl reload apache2

echo "✅ Apache configured"

# SSL Certificate
echo ""
echo "🔐 Setting up SSL certificate..."
if command -v certbot >/dev/null 2>&1; then
    sudo certbot --apache -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos --redirect
    echo "✅ SSL certificate installed"
else
    echo "⚠️  Certbot not found. Please install SSL certificate manually."
fi

# Post-deployment checks
echo ""
echo "🔍 Post-deployment checks..."

# Check if site is accessible
if curl -f -s -o /dev/null "http://localhost"; then
    echo "✅ Site is accessible"
else
    echo "⚠️  Site accessibility check failed"
fi

# Check PHP syntax
if php -l /var/www/menteego/index.php >/dev/null 2>&1; then
    echo "✅ PHP syntax check passed"
else
    echo "❌ PHP syntax errors found"
fi

# Final summary
echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📊 Deployment Summary:"
echo "   Environment: $ENVIRONMENT"
echo "   Domain: $DOMAIN"
echo "   Path: /var/www/menteego"
echo "   Backup: $BACKUP_DIR"
echo ""
echo "🔗 Your site should be accessible at:"
echo "   http://$DOMAIN"
echo "   https://$DOMAIN (if SSL was configured)"
echo ""
echo "📝 Next steps:"
echo "   1. Test the application functionality"
echo "   2. Create admin user account"
echo "   3. Configure email settings"
echo "   4. Set up monitoring and backups"
echo ""
echo "✅ Deployment complete!"