# ☁️ VPS Deployment - Quick Start Guide

## 🎯 Complete VPS Deployment in 3 Steps

### Prerequisites:
- VPS server with Ubuntu 22.04 (DigitalOcean, Linode, AWS, etc.)
- Domain name pointing to your server IP
- SSH access to your server

---

## 🚀 Option A: Automated Deployment (5 minutes)

### Step 1: Get Your VPS Server
**Recommended**: DigitalOcean $5-10/month droplet
- 1-2 CPU, 1-2GB RAM, Ubuntu 22.04
- Point your domain DNS to the server IP

### Step 2: Upload Deployment Package
```bash
# Upload the deployment package to your server
scp menteego-deployment.zip root@YOUR_SERVER_IP:/root/
```

### Step 3: Run Automated Script
```bash
# SSH to your server
ssh root@YOUR_SERVER_IP

# Extract deployment package
unzip menteego-deployment.zip

# Run automated deployment (replace yourdomain.com)
sudo ./vps-quick-deploy.sh yourdomain.com
```

**That's it!** Your site will be live at `https://yourdomain.com` in ~5 minutes with:
- ✅ LAMP stack installed
- ✅ Database configured
- ✅ SSL certificate installed
- ✅ Security configured
- ✅ Backups scheduled

---

## 🛠️ Option B: Manual Step-by-Step (15 minutes)

Follow the detailed guide in `VPS_DEPLOYMENT.md` for complete control over each step.

---

## 🎯 VPS Provider Recommendations

### 1. **DigitalOcean** (Easiest)
- **Cost**: $5-10/month
- **Setup**: 1-click Ubuntu droplet
- **Features**: Automatic backups, monitoring
- **Best for**: Beginners, reliable hosting

### 2. **Linode** 
- **Cost**: $5-10/month  
- **Setup**: Simple interface
- **Features**: Good performance
- **Best for**: Reliable hosting

### 3. **AWS Lightsail**
- **Cost**: $3.50-10/month
- **Setup**: More complex but powerful
- **Features**: Integration with AWS services
- **Best for**: Scalable solutions

### 4. **Vultr**
- **Cost**: $2.50-6/month
- **Setup**: Budget-friendly
- **Features**: Good value for money
- **Best for**: Cost-conscious deployments

---

## ⚙️ Server Specifications

### Minimum Requirements:
- **CPU**: 1 core
- **RAM**: 1GB
- **Storage**: 25GB SSD
- **OS**: Ubuntu 22.04 LTS

### Recommended for 1000+ Users:
- **CPU**: 2 cores
- **RAM**: 2GB
- **Storage**: 50GB SSD
- **OS**: Ubuntu 22.04 LTS

---

## 🔧 Domain Setup

### Step 1: Point Domain to Server
In your domain registrar (Namecheap, GoDaddy, etc.), set these DNS records:

```
Type: A
Name: @
Value: YOUR_SERVER_IP
TTL: 300

Type: A
Name: www
Value: YOUR_SERVER_IP
TTL: 300
```

### Step 2: Wait for Propagation
- Usually takes 5-30 minutes
- Check with: `nslookup yourdomain.com`

---

## 📧 Email Configuration

After deployment, edit `/var/www/menteego/.env`:

### For Gmail:
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourdomain.com
```

**Gmail Setup:**
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password (not regular password)

### For SendGrid:
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_FROM_EMAIL=noreply@yourdomain.com
```

---

## ✅ Post-Deployment Checklist

### 1. Test Your Site
- [ ] Visit https://yourdomain.com
- [ ] Homepage loads correctly
- [ ] SSL certificate working (green lock)

### 2. Create Admin Account
- [ ] Go to: https://yourdomain.com/auth/register.php
- [ ] Register with admin email
- [ ] Set role to "Admin"

### 3. Test Core Features
- [ ] User registration works
- [ ] Login/logout functions
- [ ] Dashboard displays correctly
- [ ] File upload works
- [ ] Email notifications work

### 4. Security Verification
- [ ] HTTPS redirect working
- [ ] Firewall enabled
- [ ] Sensitive files protected
- [ ] Database password secure

---

## 🚨 Common Issues & Solutions

### Issue: "Connection Refused"
**Solution**: Check if domain DNS has propagated
```bash
nslookup yourdomain.com
```

### Issue: "500 Internal Server Error"
**Solution**: Check Apache error logs
```bash
sudo tail -f /var/log/apache2/menteego_error.log
```

### Issue: Database Connection Failed
**Solution**: Verify credentials in `/var/www/menteego/.env`

### Issue: File Uploads Not Working
**Solution**: Check uploads folder permissions
```bash
sudo chmod 777 /var/www/menteego/uploads
```

### Issue: Email Not Sending
**Solution**: 
1. Verify SMTP credentials in `.env`
2. For Gmail: Enable App Passwords
3. Test with a simple email first

---

## 📊 Monitoring Your Site

### Check Site Status:
```bash
# Server resources
htop

# Apache status
sudo systemctl status apache2

# Error logs
sudo tail -f /var/log/apache2/menteego_error.log

# Access logs
sudo tail -f /var/log/apache2/menteego_access.log
```

### Backup Verification:
```bash
# Check if backups are running
ls -la /var/backups/menteego/

# Manual backup
sudo /usr/local/bin/backup-menteego.sh
```

---

## 🔄 Updates & Maintenance

### Weekly Tasks:
- Check error logs
- Verify site functionality
- Monitor server resources

### Monthly Tasks:
- Update server packages: `sudo apt update && sudo apt upgrade`
- Review backup files
- Check SSL certificate renewal

### As Needed:
- Update application files
- Database optimization
- Security patches

---

## 🎉 You're All Set!

Your Menteego platform is now:
- ✅ **Live and secure** with HTTPS
- ✅ **Fully functional** with all features
- ✅ **Automatically backed up** daily
- ✅ **Production-ready** for 1000+ users
- ✅ **Monitored and maintained**

**Need help?** Check the logs, consult the detailed guide, or troubleshoot common issues above.

**Ready to launch your mentorship platform! 🚀**