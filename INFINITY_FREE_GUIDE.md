# 📦 Deploy to InfinityFree (100% Free Traditional Hosting)

## 🎯 Why InfinityFree?
- ✅ **Completely FREE** forever
- ✅ **Unlimited** bandwidth and storage
- ✅ **No ads** on your website
- ✅ **cPanel** interface (familiar and easy)
- ✅ **MySQL databases** included
- ✅ **Custom domains** supported
- ✅ **SSL certificates** available

## 🚀 Step-by-Step Deployment (10 minutes)

### Step 1: Sign Up for InfinityFree
1. Go to **infinityfree.net**
2. Click "Create Account"
3. Choose a subdomain (e.g., `menteego.epizy.com`)
4. Complete registration (it's free!)

### Step 2: Access cPanel
1. Login to your InfinityFree account
2. Click "Control Panel" (cPanel)
3. You'll see the familiar cPanel interface

### Step 3: Upload Your Files
1. In cPanel, click **"File Manager"**
2. Navigate to **"htdocs"** folder
3. Click **"Upload"**
4. Upload **`menteego-deployment.zip`**
5. Right-click the zip file → **"Extract"**
6. Delete the zip file after extraction

### Step 4: Create Database
1. In cPanel, click **"MySQL Databases"**
2. Create new database: `epiz_XXXXX_menteego`
3. Create database user with password
4. Add user to database with **"All Privileges"**
5. Note down: database name, username, password

### Step 5: Import Database Schema
1. In cPanel, click **"phpMyAdmin"**
2. Select your database
3. Click **"Import"** tab
4. Upload **"database/schema.sql"**
5. Click **"Go"** to import

### Step 6: Configure Environment
1. In File Manager, edit **".env"** file
2. Update with your database details:

```env
# Database Configuration
DB_HOST=sql200.epizy.com
DB_PORT=3306
DB_NAME=epiz_XXXXX_menteego
DB_USER=epiz_XXXXX_user
DB_PASS=your_database_password

# Application Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://menteego.epizy.com
APP_KEY=your-32-character-key

# Email Configuration (Gmail example)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@menteego.epizy.com
MAIL_FROM_NAME="ACES Menteego"
```

### Step 7: Set File Permissions
1. In File Manager, select **"uploads"** folder
2. Right-click → **"Permissions"**
3. Set to **"777"** (drwxrwxrwx)
4. Click **"Change Permissions"**

### Step 8: Test Your Site
1. Visit your domain: **https://menteego.epizy.com**
2. You should see the Menteego homepage!
3. Test registration: **https://menteego.epizy.com/auth/register.php**

---

## 🔧 InfinityFree Specific Settings

### Database Connection Details:
- **Host**: `sql200.epizy.com` (or similar)
- **Port**: `3306`
- **Database**: `epiz_XXXXX_menteego`
- **Username**: `epiz_XXXXX_username`

### File Upload Limits:
- **Max file size**: 10MB
- **Max execution time**: 30 seconds
- **Memory limit**: 256MB

### Email Settings:
InfinityFree doesn't support mail() function, so use SMTP:
- Gmail SMTP (recommended)
- SendGrid
- Mailgun

---

## 🆓 Alternative Free Hosting Options

### 1. **000webhost**
- Similar to InfinityFree
- 1GB storage limit
- No ads
- Process: Same as InfinityFree

### 2. **FreeHosting.com**
- 10GB storage
- MySQL databases
- cPanel included

### 3. **Byet.host**
- 5GB storage
- No ads
- Good performance

---

## ✅ Post-Deployment Checklist

### Test Core Features:
- [ ] Homepage loads correctly
- [ ] User registration works
- [ ] Login/logout functions
- [ ] Dashboard displays properly
- [ ] File uploads work
- [ ] Database operations function

### Setup Admin Account:
1. Go to registration page
2. Register with your admin email
3. Set role to "Admin"
4. Login and access admin features

### Configure Email:
1. Set up Gmail App Password
2. Update MAIL_* variables in .env
3. Test email sending

---

## 🚨 Common Issues & Solutions

### Issue: "Database Connection Failed"
**Solution**: 
- Check database host (usually sql200.epizy.com)
- Verify database name format: `epiz_XXXXX_dbname`
- Ensure user has privileges

### Issue: "File Upload Errors"
**Solution**:
- Set uploads folder permission to 777
- Check if uploads directory exists
- Verify file size limits

### Issue: "Email Not Sending"
**Solution**:
- InfinityFree blocks mail() function
- Use SMTP (Gmail, SendGrid)
- Enable "Less secure apps" or use App Password

### Issue: "500 Internal Server Error"
**Solution**:
- Check .htaccess file compatibility
- Verify PHP version (7.4+ supported)
- Check error logs in cPanel

---

## 🎯 Why This Is Perfect for Students:

### ✅ Advantages:
- **$0 cost** - Perfect for students
- **No time limits** - Keep it running forever
- **Professional features** - cPanel, databases, SSL
- **Easy to use** - Familiar hosting environment
- **No technical setup** - Point and click interface

### ⚠️ Limitations:
- **Performance** - Slower than paid hosting
- **Support** - Community support only
- **Resources** - Limited CPU/memory
- **Email** - Must use external SMTP

---

## 🎉 You're Done!

Your Menteego platform is now:
- ✅ **Live and accessible** 24/7
- ✅ **Completely free** to run
- ✅ **Fully functional** with all features
- ✅ **Ready for users** to register and use

**Perfect for ACES to test and use the platform without any costs!**

Need help? Check the cPanel error logs or InfinityFree community forums.