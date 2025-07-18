# 🚅 Railway Professional Deployment - Step by Step

## 🎯 Manual Railway Deployment Guide

Since you're in a container environment, here's the manual process to deploy to Railway:

---

## 📋 Step 1: Sign Up for Railway

1. **Go to**: https://railway.app
2. **Click**: "Start a New Project"
3. **Sign up** with GitHub (recommended) or email
4. **Verify** your account

---

## 📁 Step 2: Create New Project

1. **In Railway dashboard**, click **"New Project"**
2. **Choose**: "Empty Project"
3. **Name**: "menteego" or "aces-mentorship"
4. **Click**: "Create"

---

## 🗄️ Step 3: Add MySQL Database

1. **In your project**, click **"New"** → **"Database"** → **"Add MySQL"**
2. **Wait** for database to be created (1-2 minutes)
3. **Click** on the MySQL service
4. **Go to**: "Variables" tab
5. **Copy** these values (you'll need them):
   - `MYSQL_DATABASE` (usually "railway")
   - `MYSQL_PASSWORD` (long random string)
   - `MYSQL_USER` (usually "root")

---

## 📁 Step 4: Deploy Your Application

### Option A: Upload via Web Interface
1. **Download** the deployment files to your local computer
2. **In Railway project**, click **"New"** → **"GitHub Repo"** → **"Deploy from GitHub"**
3. **Or upload** files directly via web interface

### Option B: Using Railway CLI (if you have local access)
```bash
# In your project directory
railway login
railway new
railway link <your-project-id>
railway up
```

---

## ⚙️ Step 5: Configure Environment Variables

**In Railway dashboard** → **Your app service** → **"Variables"** tab, add:

```env
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASS=<MYSQL_PASSWORD from Step 3>

APP_ENV=production
APP_DEBUG=false
APP_URL=<Your Railway app URL - will appear after deployment>
APP_KEY=<Generate a 32-character random string>

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourapp.railway.app
MAIL_FROM_NAME=ACES Menteego

MAX_UPLOAD_SIZE=10485760
ALLOWED_EXTENSIONS=pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,rar
UPLOAD_PATH=uploads/

SESSION_LIFETIME=7200
CSRF_TOKEN_LIFETIME=3600
```

---

## 🔑 Step 6: Generate Application Key

**Generate a 32-character key**:
- Use an online generator: https://generate-random.org/api-key-generator
- Or use this key: `base64:` + any 32 random characters

---

## 📊 Step 7: Import Database Schema

### Option A: Using Railway CLI
```bash
# If you have CLI access
railway connect mysql
# Then run SQL commands from database/schema.sql
```

### Option B: Manual Database Setup
1. **Get database connection details** from Railway
2. **Use a MySQL client** (like MySQL Workbench, phpMyAdmin, or online tool)
3. **Connect** using the Railway database credentials
4. **Import** the `database/schema.sql` file

### Option C: Use Railway Shell
1. **In Railway dashboard**, go to your app service
2. **Click** "Terminal" or "Shell"
3. **Run**: `php database/railway-import.php`

---

## 📦 Step 8: Upload Project Files

You have the `menteego-deployment.zip` file ready. Here's how to deploy:

### If using GitHub:
1. **Create** a new GitHub repository
2. **Upload** all project files
3. **In Railway**, connect to your GitHub repo
4. **Auto-deploy** from main branch

### If uploading manually:
1. **Extract** menteego-deployment.zip on your computer
2. **Use Railway's file upload** feature
3. **Or use the Railway CLI** to upload

---

## 🚀 Step 9: Deploy and Test

1. **Railway will automatically deploy** your app
2. **Wait** 2-3 minutes for deployment
3. **Your app URL** will appear in Railway dashboard
4. **Visit** your live site: `https://yourapp.up.railway.app`

---

## ✅ Step 10: Verify Deployment

### Test these features:
- [ ] **Homepage** loads correctly
- [ ] **Registration** form works: `/auth/register.php`
- [ ] **Login** system functions: `/auth/login.php`
- [ ] **Dashboard** displays properly: `/dashboard.php`
- [ ] **Admin** can be created

---

## 🎯 Quick Setup Alternative

If the above seems complex, here's a **simplified approach**:

### 1. Use Railway Template
1. **Go to**: Railway templates
2. **Search** for "PHP MySQL" template
3. **Deploy** template
4. **Replace** template files with Menteego files

### 2. One-Click Deploy
1. **Fork** our GitHub repository (if available)
2. **Connect** Railway to GitHub
3. **Auto-deploy** with one click

---

## 🔧 Troubleshooting

### Database Connection Issues:
- **Check** environment variables match MySQL service
- **Verify** `DB_HOST=mysql.railway.internal`
- **Ensure** database is running

### App Not Loading:
- **Check** deployment logs in Railway
- **Verify** all files uploaded correctly
- **Check** PHP version compatibility

### File Upload Errors:
- **Ensure** uploads directory exists
- **Check** file permissions
- **Verify** upload size limits

---

## 📞 Getting Help

### Railway Support:
- **Documentation**: https://docs.railway.app
- **Discord**: Railway Discord community
- **GitHub**: Railway GitHub discussions

### Project Support:
- **Check** deployment logs in Railway dashboard
- **Verify** environment variables
- **Test** database connection

---

## 🎉 Success!

Once deployed, your Menteego platform will be:
- ✅ **Live 24/7** at your Railway URL
- ✅ **Professional HTTPS** domain
- ✅ **MySQL database** fully configured
- ✅ **Auto-scaling** and monitoring
- ✅ **500 hours/month FREE**
- ✅ **Ready for production** use

**Your ACES mentorship platform is now professionally hosted! 🚀**

---

## 📱 Next Steps After Deployment

1. **Create admin account** via registration
2. **Test all features** thoroughly
3. **Configure email** settings (Gmail SMTP)
4. **Add custom domain** (optional)
5. **Monitor** usage and performance
6. **Share** with ACES members!

**Congratulations! Your professional mentorship platform is live! 🎊**