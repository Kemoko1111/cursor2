# Menteego - Mentorship Platform

## 📋 Project Overview

**Menteego** is a comprehensive mentorship platform designed to connect mentors and mentees in an educational environment. The platform facilitates meaningful mentorship relationships through an intuitive interface, real-time messaging, and resource sharing capabilities.

## 🎯 Key Features

### 🔐 Authentication & User Management
- **User Registration & Login**: Secure authentication system
- **Role-Based Access**: Separate interfaces for mentors and mentees
- **Profile Management**: Complete user profiles with academic information
- **Session Management**: Secure session handling

### 👥 Mentorship System
- **Mentor Browsing**: Mentees can browse and discover available mentors
- **Mentorship Requests**: Mentees can send requests to mentors
- **Request Management**: Mentors can view and respond to pending requests
- **Active Mentorships**: Track ongoing mentorship relationships

### 💬 Real-Time Messaging
- **AJAX-Powered Chat**: Instant message sending without page reloads
- **Conversation Management**: Organized chat interface
- **Message History**: Complete conversation history
- **Read Status**: Track message read status

### 📁 Resource Sharing
- **File Upload**: Drag & drop file sharing
- **Multiple File Support**: Share multiple files simultaneously
- **File Validation**: Size and type validation
- **Resource Display**: Special styling for shared resources

### 🎨 User Interface
- **Responsive Design**: Works on desktop and mobile devices
- **Modern UI**: Clean, professional interface using Bootstrap
- **Intuitive Navigation**: Easy-to-use navigation system
- **Real-Time Feedback**: Success/error alerts and loading states

## 🛠 Technology Stack

### Backend
- **PHP 8.0+**: Server-side logic and API endpoints
- **MySQL**: Database management system
- **PDO**: Secure database connections
- **Session Management**: User authentication and state management

### Frontend
- **HTML5**: Semantic markup structure
- **CSS3**: Styling and animations
- **JavaScript (ES6+)**: Client-side interactivity
- **Bootstrap 5.3**: Responsive UI framework
- **Font Awesome**: Icon library

### Database
- **MySQL**: Relational database
- **Tables**: users, mentorships, mentorship_requests, messages, notifications

## 📁 Project Structure

```
menteego/
├── api/
│   ├── mentor/
│   │   └── respond-request.php
│   └── messages/
│       ├── send-message.php
│       └── send-resource.php
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/
│   ├── profiles/
│   └── resources/
├── config/
│   └── app.php
├── messages.php
├── mentor-requests.php
├── browse-mentors.php
├── dashboard.php
├── setup-database.php
└── README.md
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

### Installation Steps

1. **Clone/Download Project**
   ```bash
   git clone [repository-url]
   cd menteego
   ```

2. **Database Setup**
   - Create MySQL database
   - Import database schema or run `setup-database.php`
   - Update database credentials in `config/app.php`

3. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/profiles/
   chmod 755 uploads/resources/
   ```

4. **Configuration**
   - Update database credentials in `config/app.php`
   - Set appropriate file upload limits in PHP configuration
   - Configure web server for proper routing

5. **Access Platform**
   - Navigate to your web server URL
   - Register as a mentor or mentee
   - Start using the platform

## 👥 User Roles & Workflows

### Mentee Workflow
1. **Registration**: Create account as mentee
2. **Browse Mentors**: Search and view available mentors
3. **Send Requests**: Request mentorship from chosen mentors
4. **Wait for Acceptance**: Monitor request status
5. **Start Messaging**: Communicate once request is accepted
6. **Share Resources**: Upload and share files with mentor

### Mentor Workflow
1. **Registration**: Create account as mentor
2. **Profile Setup**: Complete academic and professional profile
3. **Review Requests**: Check pending mentorship requests
4. **Accept/Reject**: Respond to mentee requests
5. **Active Mentorships**: Manage ongoing relationships
6. **Communication**: Message mentees and share resources

## 🔧 API Endpoints

### Authentication
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `GET /auth/logout.php` - User logout

### Mentorship
- `POST /api/mentor/respond-request.php` - Respond to mentorship requests

### Messaging
- `POST /api/messages/send-message.php` - Send text messages
- `POST /api/messages/send-resource.php` - Share files

## 🗄 Database Schema

### Core Tables
- **users**: User accounts and profiles
- **mentorships**: Active mentorship relationships
- **mentorship_requests**: Pending mentorship requests
- **messages**: Chat messages and shared resources
- **notifications**: System notifications

## 🔒 Security Features

- **SQL Injection Prevention**: Prepared statements with PDO
- **XSS Protection**: HTML escaping for user input
- **CSRF Protection**: Session-based security
- **File Upload Security**: Validation and secure storage
- **Password Hashing**: Secure password storage

## 📱 Responsive Design

- **Mobile-First**: Optimized for mobile devices
- **Bootstrap Grid**: Responsive layout system
- **Touch-Friendly**: Optimized for touch interactions
- **Cross-Browser**: Compatible with modern browsers

## 🎨 UI/UX Features

- **Modern Design**: Clean, professional interface
- **Intuitive Navigation**: Easy-to-use navigation
- **Real-Time Feedback**: Loading states and alerts
- **Accessibility**: Screen reader friendly
- **Performance**: Optimized for fast loading

## 🔄 Real-Time Features

- **AJAX Messaging**: Instant message sending
- **Live Updates**: Real-time conversation updates
- **File Sharing**: Instant resource sharing
- **Status Updates**: Real-time request status

## 📊 Performance Optimizations

- **Database Indexing**: Optimized database queries
- **Image Optimization**: Compressed profile images
- **Caching**: Session-based caching
- **Minified Assets**: Optimized CSS and JavaScript

## 🛡 Error Handling

- **Graceful Degradation**: System continues working with errors
- **User-Friendly Messages**: Clear error explanations
- **Logging**: Comprehensive error logging
- **Recovery**: Automatic error recovery where possible

## 🔮 Future Enhancements

- **Video Calls**: Real-time video communication
- **Calendar Integration**: Schedule meetings
- **Progress Tracking**: Monitor mentorship progress
- **Analytics Dashboard**: Usage statistics
- **Mobile App**: Native mobile application
- **Advanced Search**: Enhanced mentor discovery
- **Group Mentorship**: Multiple mentees per mentor

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🏆 Acknowledgments

- **Bootstrap Team**: For the excellent UI framework
- **Font Awesome**: For the comprehensive icon library
- **PHP Community**: For the robust backend language
- **MySQL Team**: For the reliable database system

---

**Menteego** - Connecting mentors and mentees for meaningful learning relationships.