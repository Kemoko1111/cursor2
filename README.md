# Menteego - Mentor-Mentee Matching Platform

## Overview

Menteego is a web-based mentor-mentee matching platform designed specifically for the ACES (Academic Computing Excellence Society) organization. The platform intelligently matches mentors with mentees based on user-defined preferences, skills, and availability.

## Features

### Core Functionality
- **User Registration & Authentication**
  - ACES domain email verification
  - Secure password handling with bcrypt
  - Email verification system
  - Password reset functionality

- **Intelligent Matching System**
  - Skill-based mentor-mentee matching
  - Availability scheduling
  - Department and academic year filtering
  - Preference-based recommendations

- **Mentorship Management**
  - Request system (mentees can send multiple requests)
  - Mentor capacity limits (maximum 3 mentees)
  - Mentee restriction (only 1 active mentor at a time)
  - Accept/reject request functionality

- **Messaging System**
  - Real-time messaging between mentors and mentees
  - Message history and threading
  - Unread message indicators
  - Email notifications for new messages

- **User Profile Management**
  - Comprehensive profile creation
  - Skill management (teaching and learning)
  - Profile image upload
  - Availability scheduling

- **Notification System**
  - In-app notifications
  - Email notifications for key events
  - Notification preferences

- **Admin Dashboard**
  - User management
  - Mentorship oversight
  - System statistics
  - Role-based access control

### Technical Features
- **Modern UI/UX**
  - Responsive Bootstrap 5 design
  - Modern gradient themes
  - Mobile-friendly interface
  - Accessibility considerations

- **Security**
  - SQL injection prevention with PDO
  - CSRF protection
  - Session management
  - Input sanitization
  - Role-based access control

- **Database Design**
  - Normalized MySQL database
  - Proper indexing for performance
  - Foreign key constraints
  - Audit trails and logging

## Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 8.0+** - Database management
- **PDO** - Database abstraction layer

### Frontend
- **HTML5** - Markup
- **CSS3** - Styling with custom variables
- **JavaScript (ES6+)** - Client-side functionality
- **Bootstrap 5.3** - CSS framework
- **Font Awesome 6** - Icons

### Development Tools
- **Git** - Version control
- **Composer** (optional) - Dependency management
- **PHPMailer** (optional) - Enhanced email functionality

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 8.0 or higher
- mod_rewrite enabled (for Apache)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/menteego.git
   cd menteego
   ```

2. **Configure the database**
   - Create a MySQL database named `menteego_db`
   - Import the schema from `database/schema.sql`
   ```sql
   mysql -u root -p menteego_db < database/schema.sql
   ```

3. **Configure environment variables**
   - Copy `.env.example` to `.env`
   - Update database credentials and email settings
   ```bash
   cp .env.example .env
   ```

4. **Set up file permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/profiles/
   ```

5. **Configure web server**
   - Set document root to the project directory
   - Enable mod_rewrite for Apache
   - Configure virtual host if needed

6. **Test the installation**
   - Navigate to your domain/localhost
   - You should see the Menteego homepage

## Configuration

### Environment Variables

```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=menteego_db
DB_USER=root
DB_PASS=your_password

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password

# Organization Settings
ORG_NAME="ACES - Academic Computing Excellence Society"
ORG_EMAIL=admin@aces.org
ORG_DOMAIN=aces.org
```

### Database Configuration

The application uses a normalized database design with the following key tables:
- `users` - User accounts and profiles
- `skills` - Available skills in the system
- `user_skills` - Many-to-many relationship for user skills
- `mentorship_requests` - Mentorship requests
- `mentorships` - Active mentorship relationships
- `messages` - Messaging system
- `notifications` - System notifications

## Usage

### For Students (Mentees)

1. **Registration**
   - Register with your ACES email address
   - Complete your profile with academic information
   - Specify skills you want to learn

2. **Finding Mentors**
   - Browse available mentors
   - Use filters to find suitable matches
   - View mentor profiles and skills

3. **Requesting Mentorship**
   - Send mentorship requests to multiple mentors
   - Include personal message and goals
   - Wait for mentor responses

4. **Active Mentorship**
   - Communicate through the messaging system
   - Track mentorship progress
   - Complete mentorship when goals are achieved

### For Mentors

1. **Registration**
   - Register with your ACES email address
   - Complete your profile with expertise areas
   - Specify skills you can teach
   - Set your availability schedule

2. **Managing Requests**
   - Review incoming mentorship requests
   - Accept or decline based on capacity and fit
   - Provide feedback to mentees

3. **Mentoring**
   - Guide up to 3 mentees simultaneously
   - Use messaging system for communication
   - Track mentee progress and goals

### For Administrators

1. **User Management**
   - View and manage all users
   - Suspend or activate accounts
   - Assign roles and permissions

2. **System Oversight**
   - Monitor mentorship activities
   - View system statistics
   - Manage skills and categories

## File Structure

```
menteego/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   └── schema.sql
├── models/
│   ├── User.php
│   ├── Mentorship.php
│   └── Message.php
├── services/
│   └── EmailService.php
├── uploads/
│   └── profiles/
├── index.php
├── dashboard.php
└── README.md
```

## Security Considerations

- **Input Validation**: All user inputs are sanitized and validated
- **SQL Injection Prevention**: Using PDO prepared statements
- **Password Security**: Bcrypt hashing with salt
- **Session Security**: Secure session configuration
- **Email Domain Restriction**: Only ACES domain emails allowed
- **Role-Based Access**: Proper authorization checks
- **File Upload Security**: Type and size validation for uploads

## Browser Support

Menteego supports all modern browsers:
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Opera 57+

## Performance Considerations

- **Database Indexing**: Proper indexes on frequently queried columns
- **Image Optimization**: Automatic image resizing for uploads
- **Caching**: Browser caching for static assets
- **Lazy Loading**: Progressive loading for large datasets
- **CDN Integration**: Using CDN for Bootstrap and Font Awesome

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Development Guidelines

- Follow PSR-4 autoloading standards
- Use meaningful variable and function names
- Comment complex logic
- Validate all inputs
- Handle errors gracefully
- Write responsive CSS
- Test across different browsers

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Check database credentials in `.env`
   - Verify MySQL service is running
   - Ensure database exists

2. **Email Not Sending**
   - Verify SMTP settings
   - Check email credentials
   - Ensure less secure apps are enabled (Gmail)

3. **File Upload Issues**
   - Check directory permissions
   - Verify upload size limits
   - Ensure proper file types

4. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Email: admin@aces.org
- Create an issue on GitHub
- Contact the ACES technical team

## Acknowledgments

- ACES organization for project requirements
- Bootstrap team for the CSS framework
- Font Awesome for icons
- PHP community for excellent documentation