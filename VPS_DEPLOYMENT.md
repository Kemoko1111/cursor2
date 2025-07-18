# ☁️ VPS/Cloud Server Deployment - Menteego Platform

## 🎯 Step-by-Step VPS Deployment Guide

### 📋 Prerequisites Checklist
- [ ] VPS/Cloud server (Ubuntu 20.04/22.04 recommended)
- [ ] Domain name pointing to your server IP
- [ ] SSH access to your server
- [ ] Root or sudo privileges

## 🚀 Phase 1: Get Your VPS Server

### Recommended VPS Providers:
1. **DigitalOcean** (Recommended) - $5-10/month
   - Easy setup, great documentation
   - 1-click Ubuntu 22.04 deployment
   - Automatic backups available

2. **Linode** - $5-10/month
   - Reliable performance
   - Good for beginners

3. **AWS Lightsail** - $3.50-10/month
   - Integration with other AWS services
   - More complex but powerful

4. **Vultr** - $2.50-6/month
   - Budget-friendly option

### Server Specifications:
- **Minimum**: 1 CPU, 1GB RAM, 25GB SSD
- **Recommended**: 2 CPU, 2GB RAM, 50GB SSD
- **OS**: Ubuntu 22.04 LTS

---

## 🔧 Phase 2: Initial Server Setup

### Step 1: Connect to Your Server
```bash
# Replace YOUR_SERVER_IP with your actual server IP
ssh root@YOUR_SERVER_IP

# If using a key file:
ssh -i /path/to/your/key.pem root@YOUR_SERVER_IP
```

### Step 2: Update System
```bash
# Update package lists and upgrade system
sudo apt update && sudo apt upgrade -y

# Install essential tools
sudo apt install -y curl wget unzip git
```

### Step 3: Create Non-Root User (Security Best Practice)
```bash
# Create new user
adduser menteego
usermod -aG sudo menteego

# Switch to new user
su - menteego
```

---

## 🛠️ Phase 3: Install LAMP Stack

### Step 1: Install Apache Web Server
```bash
# Install Apache
sudo apt install -y apache2

# Start and enable Apache
sudo systemctl start apache2
sudo systemctl enable apache2

# Check status
sudo systemctl status apache2
```

### Step 2: Install MySQL Database
```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation
```

**MySQL Setup Prompts:**
- Remove anonymous users? **Y**
- Disallow root login remotely? **Y**
- Remove test database? **Y**
- Reload privilege tables? **Y**

### Step 3: Install PHP
```bash
# Install PHP and required extensions
sudo apt install -y php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd libapache2-mod-php8.1

# Enable PHP module
sudo a2enmod php8.1

# Restart Apache
sudo systemctl restart apache2
```

### Step 4: Test LAMP Stack
```bash
# Create test PHP file
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php

# Visit: http://YOUR_SERVER_IP/info.php
# You should see PHP information page

# Remove test file for security
sudo rm /var/www/html/info.php
```

---

## 📁 Phase 4: Deploy Menteego Application

### Step 1: Prepare Application Directory
```bash
# Create application directory
sudo mkdir -p /var/www/menteego

# Set ownership
sudo chown -R menteego:www-data /var/www/menteego
```

### Step 2: Upload Application Files

**Option A: Using SCP (from your local machine)**
```bash
# Upload the deployment package
scp menteego-deployment.zip menteego@YOUR_SERVER_IP:/home/menteego/

# SSH back to server and extract
ssh menteego@YOUR_SERVER_IP
cd /home/menteego
unzip menteego-deployment.zip -d /var/www/menteego/
```

**Option B: Using Git (if you have a repository)**
```bash
# Clone from repository
cd /var/www/menteego
git clone https://github.com/yourusername/menteego.git .
```

**Option C: Direct Download (if files are hosted)**
```bash
# Download and extract
cd /var/www/menteego
wget https://yoursite.com/menteego-deployment.zip
unzip menteego-deployment.zip
rm menteego-deployment.zip
```

### Step 3: Set Proper Permissions
```bash
# Set ownership and permissions
sudo chown -R www-data:www-data /var/www/menteego
sudo chmod -R 755 /var/www/menteego
sudo chmod -R 777 /var/www/menteego/uploads
sudo chmod 600 /var/www/menteego/.env
```

---

## 🗄️ Phase 5: Database Setup

### Step 1: Create Database and User
```bash
# Login to MySQL
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE menteego CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'secure_password' with a strong password)
CREATE USER 'menteego_user'@'localhost' IDENTIFIED BY 'secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON menteego.* TO 'menteego_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### Step 2: Import Database Schema
```bash
# Import the database schema
mysql -u menteego_user -p menteego < /var/www/menteego/database/schema.sql
```

### Step 3: Verify Database
```bash
# Check if tables were created
mysql -u menteego_user -p menteego -e "SHOW TABLES;"
```

---

## ⚙️ Phase 6: Configure Application

### Step 1: Create Environment File
```bash
# Copy environment template
cd /var/www/menteego
cp .env.example .env

# Edit configuration
nano .env
```

**Edit `.env` file with your details:**
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=menteego
DB_USER=menteego_user
DB_PASS=secure_password

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=your-32-character-secret-key-here

# Email Configuration (Gmail example)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME="ACES Menteego"

# File Upload Configuration
MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar
UPLOAD_PATH=uploads/

# Security
SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600
```

### Step 2: Generate Application Key
```bash
# Generate a secure 32-character key
openssl rand -base64 32

# Copy the output and paste it as APP_KEY in .env file
```

---

## 🌐 Phase 7: Configure Apache Virtual Host

### Step 1: Create Virtual Host Configuration
```bash
# Create virtual host file
sudo nano /etc/apache2/sites-available/menteego.conf
```

**Add this configuration (replace yourdomain.com):**
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
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
    
    ErrorLog ${APACHE_LOG_DIR}/menteego_error.log
    CustomLog ${APACHE_LOG_DIR}/menteego_access.log combined
</VirtualHost>
```

### Step 2: Enable Site and Modules
```bash
# Enable required Apache modules
sudo a2enmod rewrite headers deflate expires

# Enable the site
sudo a2ensite menteego.conf

# Disable default Apache site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

---

## 🔐 Phase 8: SSL Certificate (Let's Encrypt)

### Step 1: Install Certbot
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache
```

### Step 2: Obtain SSL Certificate
```bash
# Get SSL certificate (replace yourdomain.com)
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Follow the prompts:
# - Enter email address
# - Agree to terms of service
# - Choose whether to share email with EFF
# - Select redirect option (recommended)
```

### Step 3: Test Auto-Renewal
```bash
# Test certificate renewal
sudo certbot renew --dry-run
```

---

## 🔥 Phase 9: Firewall Configuration

### Step 1: Configure UFW Firewall
```bash
# Enable UFW
sudo ufw enable

# Allow SSH (IMPORTANT: Do this first!)
sudo ufw allow 22

# Allow HTTP and HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Check status
sudo ufw status
```

---

## 🧪 Phase 10: Testing & Verification

### Step 1: Test Website Access
```bash
# Test HTTP redirect (should redirect to HTTPS)
curl -I http://yourdomain.com

# Test HTTPS
curl -I https://yourdomain.com
```

### Step 2: Check Logs
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/menteego_error.log

# Check Apache access logs
sudo tail -f /var/log/apache2/menteego_access.log
```

### Step 3: Verify Application
Visit your domain and test:
- [ ] Homepage loads correctly
- [ ] Registration form works
- [ ] Login system functions
- [ ] Dashboard displays
- [ ] File uploads work (test in resources section)

---

## 🚀 Phase 11: Post-Deployment Tasks

### Step 1: Create Admin Account
1. Visit: `https://yourdomain.com/auth/register.php`
2. Register with your admin email
3. Set role to "Admin"
4. Complete the registration process

### Step 2: Configure Email Settings
If using Gmail:
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password in `.env` file

### Step 3: Set Up Monitoring
```bash
# Install monitoring tools
sudo apt install -y htop iotop

# Set up log rotation
sudo nano /etc/logrotate.d/apache2
```

### Step 4: Configure Backups
```bash
# Create backup script
sudo nano /usr/local/bin/backup-menteego.sh
```

**Backup Script:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/menteego"
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u menteego_user -p menteego > $BACKUP_DIR/database_$DATE.sql

# Files backup
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/menteego

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-menteego.sh

# Add to crontab (daily backup at 2 AM)
echo "0 2 * * * /usr/local/bin/backup-menteego.sh" | sudo crontab -
```

---

## ✅ Deployment Complete!

### 🎉 Your Menteego platform is now live at:
- **HTTP**: http://yourdomain.com (redirects to HTTPS)
- **HTTPS**: https://yourdomain.com

### 📊 Next Steps:
1. **Test all functionality** thoroughly
2. **Create admin account** and configure settings
3. **Set up email notifications**
4. **Monitor server performance**
5. **Regular backups and updates**

### 🆘 Need Help?
- **Error logs**: `sudo tail -f /var/log/apache2/menteego_error.log`
- **PHP errors**: Check application error logs
- **Database issues**: Verify `.env` credentials
- **Email problems**: Test SMTP settings

**Congratulations! Your production-ready Menteego platform is now deployed and secure! 🎉**