# 🚀 Menteego Platform Deployment Guide

## 📋 Pre-Deployment Checklist

Before deploying, ensure you have:
- [ ] Domain name (e.g., menteego.aces.org)
- [ ] SSL certificate (Let's Encrypt recommended)
- [ ] MySQL database credentials
- [ ] SMTP email service (Gmail, SendGrid, etc.)
- [ ] Web hosting service access

## 🏗️ Deployment Options

### Option 1: Shared Hosting (Easiest) 📦
**Best for: Small to medium deployments, budget-friendly**
**Recommended providers: cPanel hosting, Hostinger, SiteGround**

### Option 2: VPS/Cloud Server (Recommended) ☁️
**Best for: Full control, scalability, production use**
**Recommended providers: DigitalOcean, Linode, AWS, Google Cloud**

### Option 3: Platform-as-a-Service (Simplest) 🎯
**Best for: Quick deployment, automatic scaling**
**Recommended providers: Heroku, Railway, PlanetScale**

---

## 🎯 Option 1: Shared Hosting Deployment

### Step 1: Prepare Files for Upload
```bash
# Create deployment package
zip -r menteego-deployment.zip . -x "*.git*" "node_modules/*" "*.env" "PREVIEW_SETUP.md" "TROUBLESHOOTING.md"
```

### Step 2: Upload to Hosting
1. Access your cPanel/hosting control panel
2. Go to File Manager
3. Navigate to `public_html` directory
4. Upload and extract the zip file
5. Set folder permissions: `uploads/` → 755

### Step 3: Create Database
1. In cPanel, go to "MySQL Databases"
2. Create database: `your_username_menteego`
3. Create user with all privileges
4. Note down: database name, username, password

### Step 4: Configure Environment
Create `.env` file in root directory:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_username_menteego
DB_USER=your_username_dbuser
DB_PASS=your_secure_password

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=your-32-character-secret-key

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME="ACES Menteego"
```

### Step 5: Import Database
1. Access phpMyAdmin in cPanel
2. Select your database
3. Import `database/schema.sql`
4. Verify all tables are created

---

## ☁️ Option 2: VPS/Cloud Server Deployment

### Step 1: Server Setup (Ubuntu 20.04/22.04)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP stack
sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd libapache2-mod-php8.1

# Install Composer (for dependencies)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Certbot for SSL
sudo apt install -y certbot python3-certbot-apache
```

### Step 2: Configure Apache
```bash
# Create virtual host
sudo nano /etc/apache2/sites-available/menteego.conf
```

Add this configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/menteego
    
    <Directory /var/www/menteego>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/menteego_error.log
    CustomLog ${APACHE_LOG_DIR}/menteego_access.log combined
</VirtualHost>
```

```bash
# Enable site and modules
sudo a2ensite menteego.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 3: Deploy Application
```bash
# Create directory
sudo mkdir -p /var/www/menteego
cd /var/www/menteego

# Clone or upload your files
git clone <your-repo-url> .
# OR upload files via SCP/SFTP

# Set permissions
sudo chown -R www-data:www-data /var/www/menteego
sudo chmod -R 755 /var/www/menteego
sudo chmod -R 777 /var/www/menteego/uploads
```

### Step 4: Configure Database
```bash
# Secure MySQL
sudo mysql_secure_installation

# Create database
sudo mysql -u root -p
```

```sql
CREATE DATABASE menteego CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'menteego_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON menteego.* TO 'menteego_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u menteego_user -p menteego < database/schema.sql
```

### Step 5: SSL Certificate
```bash
# Get SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

---

## 🎯 Option 3: Platform-as-a-Service (Heroku)

### Step 1: Prepare for Heroku
Create `Procfile`:
```
web: php -S 0.0.0.0:$PORT -t .
```

Create `composer.json`:
```json
{
    "require": {
        "php": "^8.1"
    }
}
```

### Step 2: Deploy to Heroku
```bash
# Install Heroku CLI and login
heroku login

# Create app
heroku create your-app-name

# Add database addon
heroku addons:create cleardb:ignite

# Set environment variables
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_KEY=your-32-character-key

# Deploy
git add .
git commit -m "Deploy to Heroku"
git push heroku main
```

---

## ⚙️ Post-Deployment Configuration

### 1. Environment Variables
Update `.env` with production values:
```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=your-db-host
DB_NAME=your-db-name
DB_USER=your-db-user
DB_PASS=your-secure-password
MAIL_HOST=your-smtp-host
```

### 2. Security Hardening
```bash
# Hide sensitive files
echo "deny from all" > .env
echo "deny from all" > database/.htaccess
```

### 3. Performance Optimization
- Enable gzip compression
- Set up caching headers
- Optimize images in `assets/`
- Consider using a CDN for static assets

### 4. Monitoring Setup
- Set up error logging
- Configure uptime monitoring
- Set up backup schedules

---

## 🔧 Domain & DNS Configuration

### DNS Records Setup
Point your domain to your server:
```
Type: A
Name: @
Value: YOUR_SERVER_IP

Type: A  
Name: www
Value: YOUR_SERVER_IP
```

### Email DNS (if using custom domain)
```
Type: MX
Name: @
Value: mail.yourdomain.com
Priority: 10
```

---

## 📊 Production Checklist

### Before Going Live:
- [ ] Database is properly configured and seeded
- [ ] All environment variables are set
- [ ] SSL certificate is installed and working
- [ ] File permissions are correct (uploads folder writable)
- [ ] Email sending is tested and working
- [ ] Error logging is configured
- [ ] Backup system is in place
- [ ] Admin account is created
- [ ] Security headers are configured

### Testing in Production:
- [ ] Registration process works
- [ ] Email verification works
- [ ] Login/logout functions
- [ ] File uploads work
- [ ] Database operations are working
- [ ] Responsive design on mobile
- [ ] Cross-browser compatibility
- [ ] Page load times are acceptable

---

## 🚨 Common Deployment Issues & Solutions

### Issue 1: Database Connection Errors
**Solution**: Verify database credentials in `.env` file

### Issue 2: File Upload Permissions
**Solution**: 
```bash
sudo chmod 777 uploads/
sudo chown www-data:www-data uploads/
```

### Issue 3: Email Not Sending
**Solution**: Check SMTP credentials and enable "Less secure apps" for Gmail

### Issue 4: SSL Issues
**Solution**: Verify DNS propagation and certificate installation

### Issue 5: 500 Internal Server Error
**Solution**: Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks:
1. **Weekly**: Check error logs and site uptime
2. **Monthly**: Update PHP and server packages
3. **Quarterly**: Review and update security measures
4. **As needed**: Database optimization and cleanup

### Backup Strategy:
- **Daily**: Database backups
- **Weekly**: Full file system backups
- **Monthly**: Offsite backup verification

---

## 🎉 You're Ready to Deploy!

Choose the deployment option that best fits your needs and technical expertise. For production use with 1000+ users, I recommend **Option 2 (VPS/Cloud Server)** for the best performance and control.

Need help with any specific step? Let me know which deployment option you'd like to proceed with!