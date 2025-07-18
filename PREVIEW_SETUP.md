# 🚀 How to Preview Menteego Platform

## Current Status
✅ **Web Server is Running!**

Your Menteego platform is now accessible at: **http://localhost:8000**

## 📋 What You Can Preview

### 1. **Landing Page**
- Open: http://localhost:8000
- View the modern landing page with features showcase
- Responsive design that works on mobile and desktop

### 2. **User Registration**
- Go to: http://localhost:8000/auth/register.php
- Test the registration form (no actual email verification in preview mode)
- Try both mentor and mentee registration

### 3. **User Login**
- Go to: http://localhost:8000/auth/login.php
- Test the login interface

### 4. **Dashboard Preview**
- After login: http://localhost:8000/dashboard.php
- See role-based dashboards for mentors/mentees

### 5. **Resources Section**
- Access: http://localhost:8000/resources.php
- File upload and sharing interface

### 6. **Reports & Analytics**
- View: http://localhost:8000/reports.php
- Interactive charts and statistics

## ⚠️ Preview Limitations

Since this is a preview without a database:
- **No data persistence** - registrations won't be saved
- **No email sending** - verification emails won't work
- **No file uploads** - file operations may fail
- **No login sessions** - authentication won't persist

## 🛠️ To Get Full Functionality

1. **Set up MySQL database:**
   ```bash
   # Install MySQL/MariaDB
   sudo apt install mysql-server
   
   # Import the database schema
   mysql -u root -p < database/schema.sql
   ```

2. **Configure database connection:**
   - Edit `.env` file with your database credentials
   - Update DB_USER and DB_PASS

3. **Set up email service:**
   - Configure SMTP settings in `.env`
   - Or use a service like Mailcatcher for testing

## 🎨 Visual Features to Check Out

- **Modern UI** with Bootstrap 5
- **Dark mode toggle** (available in dashboard)
- **Responsive design** (test on different screen sizes)
- **Interactive charts** in reports section
- **Smooth animations** and transitions
- **Professional color scheme** with gradients

## 🔧 Development Server Commands

```bash
# Start server (if not running)
php -S localhost:8000 -t .

# Stop server
# Press Ctrl+C in the terminal where server is running

# Check server status
curl http://localhost:8000
```

Enjoy exploring your Menteego platform! 🎓✨