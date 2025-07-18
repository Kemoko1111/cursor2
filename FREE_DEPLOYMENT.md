# 🆓 FREE Deployment Options for Menteego Platform

## 🎯 Best Free Hosting Solutions

### Option 1: Railway (Recommended) 🚅
**Features**: 500 hours/month free, automatic deployments, built-in database
**Perfect for**: Production use, easy setup

### Option 2: Heroku 🔷
**Features**: 1000 dyno hours/month, add-ons available
**Perfect for**: Reliable hosting, good documentation

### Option 3: Vercel + PlanetScale 🌐
**Features**: Unlimited deployments, serverless database
**Perfect for**: Modern deployment, great performance

### Option 4: InfinityFree + 000webhost 📦
**Features**: Traditional PHP hosting, cPanel included
**Perfect for**: Familiar shared hosting experience

---

## 🚅 Option 1: Railway Deployment (EASIEST)

### Step 1: Prepare for Railway
```bash
# Install Railway CLI
npm install -g @railway/cli

# Login to Railway
railway login
```

### Step 2: Create Railway Project
```bash
# Create new project
railway new menteego

# Deploy
railway up
```

### Step 3: Add Database
1. Go to Railway dashboard
2. Click "Add Service" → "Database" → "MySQL"
3. Note the connection details

### Step 4: Set Environment Variables
In Railway dashboard, add these variables:
```env
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASS=your-railway-mysql-password
APP_ENV=production
APP_DEBUG=false
```

**Railway Limits**: 500 hours/month (enough for most use cases)

---

## 🔷 Option 2: Heroku Deployment

### Step 1: Prepare Files
We already have `Procfile` and `composer.json` ready!

### Step 2: Deploy to Heroku
```bash
# Install Heroku CLI
# Download from: https://devcenter.heroku.com/articles/heroku-cli

# Login
heroku login

# Create app
heroku create your-menteego-app

# Add database addon (FREE tier)
heroku addons:create cleardb:ignite

# Get database URL
heroku config:get CLEARDB_DATABASE_URL

# Set environment variables
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
```

### Step 3: Configure Database
```bash
# Parse the CLEARDB_DATABASE_URL and set individual variables
heroku config:set DB_HOST=your-db-host
heroku config:set DB_NAME=your-db-name
heroku config:set DB_USER=your-db-user
heroku config:set DB_PASS=your-db-password
```

### Step 4: Deploy
```bash
# Deploy
git add .
git commit -m "Deploy to Heroku"
git push heroku main

# Import database
heroku run php database/import.php
```

**Heroku Limits**: 1000 dyno hours/month (sleeps after 30min inactivity)

---

## 🌐 Option 3: Vercel + PlanetScale

### Step 1: Deploy to Vercel
```bash
# Install Vercel CLI
npm install -g vercel

# Deploy
vercel

# Follow prompts to connect GitHub
```

### Step 2: Setup PlanetScale Database
1. Sign up at planetscale.com
2. Create new database
3. Get connection string
4. Add to Vercel environment variables

### Vercel Configuration (`vercel.json`):
```json
{
  "functions": {
    "api/*.php": {
      "runtime": "vercel-php@0.6.0"
    }
  },
  "routes": [
    { "src": "/(.*)", "dest": "/$1" }
  ]
}
```

**Vercel + PlanetScale Limits**: Generous free tiers, great performance

---

## 📦 Option 4: Traditional Free Hosting

### InfinityFree (Recommended Free Shared Hosting)
**Features**: 
- Unlimited bandwidth and storage
- cPanel access
- MySQL databases
- No ads
- Custom domains

### Steps:
1. Sign up at infinityfree.net
2. Create account and subdomain
3. Upload `menteego-deployment.zip` via File Manager
4. Create MySQL database in cPanel
5. Import `database/schema.sql` via phpMyAdmin
6. Edit `.env` file with database details

### 000webhost (Alternative)
Similar process to InfinityFree but with 1GB storage limit.

---

## 🚀 SUPER QUICK: Railway Deployment (5 Minutes)

I'll create a Railway-ready setup for you:

### Step 1: Install Railway CLI
```bash
# Windows (using npm)
npm install -g @railway/cli

# Mac (using brew)
brew install railway/tap/railway

# Linux (using curl)
curl -fsSL https://railway.app/install.sh | sh
```

### Step 2: Login and Deploy
```bash
# Login to Railway
railway login

# Initialize project
railway new

# Select "Deploy from GitHub repo" or "Empty project"
# Choose "Empty project" for now

# Deploy current directory
railway up
```

### Step 3: Add Database
1. Go to railway.app dashboard
2. Click your project
3. Click "New" → "Database" → "Add MySQL"
4. Copy the connection details

### Step 4: Set Environment Variables
In Railway dashboard → Variables:
```env
DB_HOST=mysql.railway.internal
DB_PORT=3306
DB_NAME=railway
DB_USER=root
DB_PASS=(auto-generated)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.railway.app
```

### Step 5: Import Database
```bash
# Connect to Railway MySQL and import schema
railway run mysql -h mysql.railway.internal -u root -p railway < database/schema.sql
```

**Done!** Your app will be live at `https://your-app.railway.app`

---

## 🆓 Comparison of Free Options

| Platform | Pros | Cons | Best For |
|----------|------|------|----------|
| **Railway** | Easy setup, good limits, built-in DB | 500 hours/month | Production use |
| **Heroku** | Reliable, good docs | Sleeps after 30min | Development/testing |
| **Vercel + PlanetScale** | Great performance, modern | More complex setup | Advanced users |
| **InfinityFree** | Traditional hosting, unlimited | Slower performance | Simple deployment |

---

## 🎯 RECOMMENDED: Railway Quick Deploy

Let me create an optimized Railway setup: