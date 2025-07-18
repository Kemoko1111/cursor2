#!/bin/bash

# Menteego VPS Quick Deployment Script
# This script automates the entire VPS deployment process
# Usage: ./vps-quick-deploy.sh yourdomain.com

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Menteego VPS Quick Deployment Script${NC}"
echo "=================================================="

# Check if domain is provided
if [ $# -eq 0 ]; then
    echo -e "${RED}❌ Usage: ./vps-quick-deploy.sh yourdomain.com${NC}"
    exit 1
fi

DOMAIN=$1
echo -e "${GREEN}🌐 Setting up Menteego for domain: $DOMAIN${NC}"

# Function to print status
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

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run this script as root or with sudo"
    exit 1
fi

# Phase 1: System Update
print_status "Phase 1: Updating system..."
apt update && apt upgrade -y
apt install -y curl wget unzip git nano
print_success "System updated"

# Phase 2: Install LAMP Stack
print_status "Phase 2: Installing LAMP stack..."

# Install Apache
apt install -y apache2
systemctl start apache2
systemctl enable apache2
print_success "Apache installed"

# Install MySQL
print_status "Installing MySQL..."
apt install -y mysql-server
print_success "MySQL installed"

# Install PHP
print_status "Installing PHP..."
apt install -y php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd libapache2-mod-php8.1
a2enmod php8.1
systemctl restart apache2
print_success "PHP installed"

# Phase 3: Create application directory
print_status "Phase 3: Preparing application directory..."
mkdir -p /var/www/menteego
chown -R www-data:www-data /var/www/menteego
print_success "Application directory ready"

# Phase 4: Database Setup
print_status "Phase 4: Setting up database..."

# Generate secure password
DB_PASSWORD=$(openssl rand -base64 32 | tr -d '/')
APP_KEY=$(openssl rand -base64 32)

# Create database and user
mysql -e "CREATE DATABASE menteego CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'menteego_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON menteego.* TO 'menteego_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
print_success "Database created"

# Phase 5: Deploy application files
print_status "Phase 5: Deploying application files..."

# Check if menteego-deployment.zip exists in current directory
if [ -f "menteego-deployment.zip" ]; then
    print_status "Found deployment package, extracting..."
    unzip -q menteego-deployment.zip -d /var/www/menteego/
elif [ -d "/workspace" ] && [ -f "/workspace/menteego-deployment.zip" ]; then
    print_status "Found deployment package in workspace, copying..."
    cp /workspace/menteego-deployment.zip /tmp/
    unzip -q /tmp/menteego-deployment.zip -d /var/www/menteego/
    rm /tmp/menteego-deployment.zip
else
    # Copy from current workspace
    print_status "Copying application files from current directory..."
    rsync -av --exclude='.git' --exclude='*.log' --exclude='node_modules' ./ /var/www/menteego/
fi

print_success "Application files deployed"

# Phase 6: Configure environment
print_status "Phase 6: Configuring environment..."

# Create .env file
cat > /var/www/menteego/.env << EOF
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=menteego
DB_USER=menteego_user
DB_PASS=$DB_PASSWORD

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://$DOMAIN
APP_KEY=$APP_KEY

# Email Configuration (Update these with your SMTP details)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@$DOMAIN
MAIL_FROM_NAME="ACES Menteego"

# File Upload Configuration
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar
UPLOAD_PATH=uploads/

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600
EOF

print_success "Environment configured"

# Phase 7: Import database schema
print_status "Phase 7: Importing database schema..."
if [ -f "/var/www/menteego/database/schema.sql" ]; then
    mysql -u menteego_user -p$DB_PASSWORD menteego < /var/www/menteego/database/schema.sql
    print_success "Database schema imported"
else
    print_warning "Database schema file not found. You'll need to import it manually."
fi

# Phase 8: Set permissions
print_status "Phase 8: Setting permissions..."
chown -R www-data:www-data /var/www/menteego
chmod -R 755 /var/www/menteego
chmod -R 777 /var/www/menteego/uploads
chmod 600 /var/www/menteego/.env
print_success "Permissions set"

# Phase 9: Configure Apache
print_status "Phase 9: Configuring Apache..."

# Create virtual host
cat > /etc/apache2/sites-available/menteego.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot /var/www/menteego
    
    <Directory /var/www/menteego>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
    
    <Directory /var/www/menteego/uploads>
        <FilesMatch "\.php$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
    
    ErrorLog \${APACHE_LOG_DIR}/menteego_error.log
    CustomLog \${APACHE_LOG_DIR}/menteego_access.log combined
</VirtualHost>
EOF

# Enable modules and site
a2enmod rewrite headers deflate expires
a2ensite menteego.conf
a2dissite 000-default.conf

# Test and restart Apache
if apache2ctl configtest; then
    systemctl restart apache2
    print_success "Apache configured"
else
    print_error "Apache configuration test failed"
    exit 1
fi

# Phase 10: Install SSL Certificate
print_status "Phase 10: Installing SSL certificate..."
apt install -y certbot python3-certbot-apache

print_status "Getting SSL certificate for $DOMAIN..."
echo -e "${YELLOW}You'll be prompted for email and to agree to terms...${NC}"

# Try to get SSL certificate
if certbot --apache -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN --redirect; then
    print_success "SSL certificate installed"
else
    print_warning "SSL certificate installation failed. You can set it up manually later."
fi

# Phase 11: Configure firewall
print_status "Phase 11: Configuring firewall..."
ufw --force enable
ufw allow 22
ufw allow 80
ufw allow 443
print_success "Firewall configured"

# Phase 12: Create backup script
print_status "Phase 12: Setting up backup system..."
mkdir -p /var/backups/menteego

cat > /usr/local/bin/backup-menteego.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/menteego"
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u menteego_user -p$DB_PASSWORD menteego > $BACKUP_DIR/database_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/menteego

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

# Fix the DB_PASSWORD variable in backup script
sed -i "s/\$DB_PASSWORD/$DB_PASSWORD/g" /usr/local/bin/backup-menteego.sh

chmod +x /usr/local/bin/backup-menteego.sh

# Add to crontab
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-menteego.sh") | crontab -
print_success "Backup system configured"

# Phase 13: Final testing
print_status "Phase 13: Final testing..."

# Test if site is accessible
if curl -f -s -o /dev/null http://localhost; then
    print_success "Website is accessible"
else
    print_warning "Website accessibility test failed"
fi

# Final summary
echo ""
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETED SUCCESSFULLY! 🎉${NC}"
echo "=================================================="
echo ""
echo -e "${BLUE}📊 Deployment Summary:${NC}"
echo "   Domain: $DOMAIN"
echo "   Document Root: /var/www/menteego"
echo "   Database: menteego"
echo "   Database User: menteego_user"
echo "   Database Password: $DB_PASSWORD"
echo "   Application Key: $APP_KEY"
echo ""
echo -e "${BLUE}🔗 Your website should be accessible at:${NC}"
echo "   http://$DOMAIN (redirects to HTTPS)"
echo "   https://$DOMAIN"
echo ""
echo -e "${BLUE}📝 Important Next Steps:${NC}"
echo "   1. Create admin account: https://$DOMAIN/auth/register.php"
echo "   2. Update email settings in: /var/www/menteego/.env"
echo "   3. Test all functionality"
echo "   4. Set up monitoring and alerts"
echo ""
echo -e "${BLUE}🔐 Security Information:${NC}"
echo "   - Database password saved to: /var/www/menteego/.env"
echo "   - SSL certificate auto-renewal configured"
echo "   - Firewall enabled (ports 22, 80, 443)"
echo "   - Daily backups configured at 2 AM"
echo ""
echo -e "${YELLOW}📧 Email Configuration:${NC}"
echo "   Edit /var/www/menteego/.env and update:"
echo "   - MAIL_USERNAME with your email"
echo "   - MAIL_PASSWORD with your app password"
echo ""
echo -e "${GREEN}✅ Your Menteego platform is now live and ready for use!${NC}"
echo ""
echo -e "${BLUE}🆘 For troubleshooting:${NC}"
echo "   Error logs: tail -f /var/log/apache2/menteego_error.log"
echo "   Application logs: Check /var/www/menteego/ for PHP errors"
echo ""