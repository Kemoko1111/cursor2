# 🔧 Troubleshooting Web Server Access

## ✅ Server Status: RUNNING

Your PHP development server is now running and accessible!

## 🌐 How to Access Your Platform:

### Option 1: Direct Access (Recommended)
**URL: http://localhost:8000**

If you're running this in:
- **Local development environment**: Use `http://localhost:8000`
- **Docker/Container**: Use `http://172.30.0.2:8000` or the container's IP
- **Remote server**: Use the server's IP address with port 8000

### Option 2: Alternative IPs
If localhost doesn't work, try these addresses:
- `http://172.30.0.2:8000`
- `http://127.0.0.1:8000`

## 🔍 Common Issues & Solutions:

### 1. "Connection Refused" Error
**Solution**: The server is now bound to `0.0.0.0:8000` which should resolve this issue.

### 2. Port Already in Use
If port 8000 is busy, start server on different port:
```bash
php -S 0.0.0.0:8080 -t .
```
Then access via `http://localhost:8080`

### 3. Permission Issues
Make sure uploads directory has proper permissions:
```bash
chmod 755 uploads/
```

### 4. Database Connection Errors
For preview purposes, the database errors can be ignored. The UI and static content will still work.

## 🔄 Restart Server
If you need to restart the server:
```bash
# Stop server
pkill -f "php -S"

# Start server again
php -S 0.0.0.0:8000 -t .
```

## 📱 What Works in Preview Mode:
- ✅ Landing page and UI
- ✅ Registration forms (UI only)
- ✅ Login interface (UI only)  
- ✅ Dashboard layouts
- ✅ Resource upload interface
- ✅ Reports and analytics UI
- ❌ Database operations (requires MySQL setup)
- ❌ Email sending (requires SMTP setup)

## 🗒️ Note:
In this preview mode, you're seeing the complete UI and frontend functionality. Database features will show errors until MySQL is configured, but all the visual elements and layouts are fully functional!