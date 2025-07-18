# 🚀 Quick Deployment Guide - Menteego Platform

## 📦 Ready-to-Deploy Package
✅ **`menteego-deployment.zip`** - Your complete deployment package is ready!

## ⚡ Quick Start Options

### 🎯 Option A: Shared Hosting (5 minutes)
**Best for beginners or small deployments**

1. **Download**: `menteego-deployment.zip` 
2. **Upload**: Extract to your cPanel/hosting `public_html` folder
3. **Database**: Create MySQL database in cPanel
4. **Configure**: Edit `.env` file with your database details
5. **Import**: Upload `database/schema.sql` via phpMyAdmin
6. **Done**: Visit your domain!

### ☁️ Option B: VPS/Cloud Server (15 minutes)
**Best for production use with 1000+ users**

```bash
# 1. Server setup (Ubuntu)
sudo apt update && sudo apt install -y apache2 mysql-server php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl

# 2. Upload and extract files
sudo mkdir -p /var/www/menteego
# Upload menteego-deployment.zip and extract

# 3. Run auto-deployment script
sudo chmod +x deploy.sh
sudo ./deploy.sh production

# 4. Done! Your site is live with SSL
```

### 🎯 Option C: One-Click Deploy (2 minutes)
**Instant deployment on modern platforms**

#### Heroku:
```bash
git add . && git commit -m "Deploy"
heroku create your-app-name
git push heroku main
```

#### Railway/Vercel:
- Connect your GitHub repo
- Auto-deploy from main branch
- Set environment variables

---

## 🔧 What You Need to Prepare

### 1. **Domain Name** 
- `yoursite.com` or `menteego.yourschool.edu`

### 2. **Database Credentials**
- MySQL database name, username, password

### 3. **Email Service** (for notifications)
- Gmail SMTP or SendGrid account

### 4. **SSL Certificate** (automatic with most providers)

---

## ⚙️ Configuration File (`.env`)
Copy and edit this template:

```env
# Database
DB_HOST=localhost
DB_NAME=menteego
DB_USER=your_username
DB_PASS=your_password

# Application
APP_URL=https://yoursite.com
APP_ENV=production
APP_DEBUG=false

# Email
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yoursite.com
```

---

## 🎉 After Deployment

### ✅ Test Checklist:
- [ ] Homepage loads correctly
- [ ] Registration form works
- [ ] Login system functions
- [ ] Dashboard displays properly
- [ ] File uploads work
- [ ] Email notifications send

### 🔐 Security Setup:
- [ ] Change default admin credentials
- [ ] Enable SSL certificate
- [ ] Configure firewall
- [ ] Set up automated backups

### 📊 Admin Account Creation:
Visit: `https://yoursite.com/auth/register.php`
- Register with admin email
- Set role to "Admin"
- Verify email (if configured)

---

## 🆘 Need Help?

### Quick Fixes:
- **Database errors**: Check `.env` credentials
- **File upload issues**: Set `uploads/` folder permissions to 777
- **Email not working**: Verify SMTP settings and "Less secure apps" for Gmail

### Support Resources:
- 📖 **Full Guide**: `DEPLOYMENT_GUIDE.md`
- 🔧 **Troubleshooting**: Apache error logs or contact hosting support
- 📧 **Email Setup**: Enable 2FA and app passwords for Gmail

---

## 🚀 Choose Your Path:

1. **🎯 Shared Hosting** → Easy setup, perfect for getting started
2. **☁️ VPS/Cloud** → Full control, best for production with many users  
3. **🎯 Platform-as-Service** → Instant deployment, automatic scaling

**Ready to deploy? Pick your option above and follow the steps!**

Your Menteego platform is production-ready and includes:
✅ User authentication & registration
✅ Mentor-mentee matching system
✅ Messaging & notifications
✅ File upload & sharing
✅ Admin dashboard & analytics
✅ Modern responsive design
✅ Security hardening & SSL support