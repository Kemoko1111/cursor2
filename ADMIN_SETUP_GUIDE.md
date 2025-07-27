# 🔐 Admin Login Setup Guide

## 🎯 **How Admins Will Login**

### **Option 1: Dedicated Admin Login (Recommended)**
- **URL**: `http://menteego.infy.uk/admin/login.php`
- **Features**: 
  - ✅ **No email domain restriction** (can use any email)
  - ✅ **Admin-only access** (checks for admin role)
  - ✅ **Professional admin interface**
  - ✅ **Secure password verification**

### **Option 2: Use Regular Login**
- **URL**: `http://menteego.infy.uk/auth/login.php`
- **Features**:
  - ✅ **Same login form** as regular users
  - ❌ **Email domain restriction** (must use ACES email)
  - ✅ **Works with existing system**

## 🚀 **Quick Setup Steps**

### **Step 1: Create Admin User**

Run this SQL in your database:

```sql
-- Create admin user
INSERT INTO users (
    first_name, 
    last_name, 
    email, 
    password_hash, 
    user_role, 
    department, 
    year_of_study, 
    status, 
    email_verified, 
    created_at
) VALUES (
    'Admin',
    'User',
    'admin@menteego.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'admin',
    'Administration',
    'N/A',
    'active',
    1,
    NOW()
);
```

### **Step 2: Upload Admin Files**

Upload these files to your server:
- `/admin/login.php` - Admin login page
- `/admin/dashboard.php` - Main admin dashboard
- `/admin/users.php` - User management

### **Step 3: Test Admin Access**

1. **Go to**: `http://menteego.infy.uk/admin/login.php`
2. **Login with**:
   - **Email**: `admin@menteego.com`
   - **Password**: `password`
3. **You'll be redirected** to the admin dashboard

## 🔑 **Default Admin Credentials**

### **Option 1: Use the SQL above**
- **Email**: `admin@menteego.com`
- **Password**: `password`

### **Option 2: Create your own admin**
```sql
-- Replace with your desired credentials
INSERT INTO users (
    first_name, last_name, email, password_hash, user_role, 
    department, year_of_study, status, email_verified, created_at
) VALUES (
    'Your', 'Name', 'your-email@example.com', 
    '$2y$10$' || password_hash('your-password', PASSWORD_DEFAULT), 
    'admin', 'Your Department', 'N/A', 'active', 1, NOW()
);
```

## 🛡 **Security Features**

### **Admin Login Security:**
- ✅ **Role-based access** - Only admin users can login
- ✅ **Password verification** - Secure password checking
- ✅ **Session management** - Secure admin sessions
- ✅ **Error handling** - Graceful error management
- ✅ **No domain restriction** - Can use any email

### **Admin Dashboard Security:**
- ✅ **Session validation** - Checks admin role on every page
- ✅ **CSRF protection** - Secure form submissions
- ✅ **Input validation** - Sanitized user inputs
- ✅ **Error logging** - Comprehensive error tracking

## 📱 **Admin Login Interface**

### **Visual Features:**
- **Professional gradient design**
- **Shield icon** for admin branding
- **Responsive layout** for all devices
- **Password visibility toggle**
- **Form validation** with JavaScript

### **User Experience:**
- **Clear admin branding**
- **Intuitive form design**
- **Helpful error messages**
- **Easy navigation** back to regular login

## 🔄 **Login Flow**

### **Admin Login Process:**
1. **User visits** `/admin/login.php`
2. **Enters** admin email and password
3. **System checks** if user exists and is admin
4. **Verifies** password hash
5. **Creates** admin session
6. **Redirects** to `/admin/dashboard.php`

### **Session Management:**
```php
// Admin session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['last_activity'] = time();
```

## 🎯 **Access Control**

### **Admin-Only Pages:**
- `/admin/login.php` - Admin login
- `/admin/dashboard.php` - Main dashboard
- `/admin/users.php` - User management
- `/admin/mentorships.php` - Mentorship management
- `/admin/requests.php` - Request monitoring
- `/admin/messages.php` - Message management
- `/admin/analytics.php` - Analytics & reports
- `/admin/settings.php` - System settings

### **Access Validation:**
```php
// Every admin page checks this
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}
```

## 🔧 **Troubleshooting**

### **Common Issues:**

**1. "Invalid email or password"**
- Check if admin user exists in database
- Verify password hash is correct
- Ensure user role is 'admin'

**2. "Access denied"**
- Check if user role is set to 'admin'
- Verify session is active
- Clear browser cookies and try again

**3. "Page not found"**
- Ensure admin files are uploaded correctly
- Check file permissions
- Verify URL paths are correct

### **Debug Steps:**
1. **Check database**: Verify admin user exists
2. **Check file permissions**: Ensure PHP files are readable
3. **Check error logs**: Look for PHP errors
4. **Test with different browser**: Clear cache and cookies

## 📊 **Admin Dashboard Features**

### **What You'll See After Login:**
- **Statistics cards** with platform metrics
- **Interactive charts** showing user growth
- **Recent activity feeds** for users and requests
- **Professional sidebar** with navigation
- **Responsive design** for all devices

### **Available Actions:**
- **View all users** with search and filtering
- **Monitor mentorships** and requests
- **Generate reports** and analytics
- **Manage system settings**
- **Export data** for analysis

## 🎉 **Ready to Use!**

Once you've completed the setup:
1. **Visit**: `http://menteego.infy.uk/admin/login.php`
2. **Login** with admin credentials
3. **Explore** the admin dashboard
4. **Manage** your platform effectively

The admin system is **fully integrated** with your existing platform and provides **comprehensive management capabilities**! 🚀