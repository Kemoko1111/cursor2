-- Menteego Database Schema
-- Normalized database design for mentor-mentee matching platform

CREATE DATABASE IF NOT EXISTS menteego_db;
USE menteego_db;

-- Users table (normalized base user information)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    bio TEXT,
    year_of_study ENUM('1', '2', '3', '4', 'graduate', 'faculty') NOT NULL,
    department VARCHAR(100) NOT NULL,
    role ENUM('mentee', 'mentor', 'admin') NOT NULL DEFAULT 'mentee',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_student_id (student_id),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Skills table (normalized skills management)
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
);

-- User skills junction table (many-to-many relationship)
CREATE TABLE user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') NOT NULL,
    years_experience DECIMAL(3,1),
    is_teaching_skill BOOLEAN DEFAULT FALSE,
    is_learning_skill BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id),
    INDEX idx_user_teaching (user_id, is_teaching_skill),
    INDEX idx_user_learning (user_id, is_learning_skill)
);

-- Availability table (mentor availability schedule)
CREATE TABLE availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_availability (user_id, day_of_week, is_available)
);

-- Mentorship requests table
CREATE TABLE mentorship_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    message TEXT,
    preferred_meeting_type ENUM('online', 'in_person', 'hybrid') DEFAULT 'online',
    goals TEXT,
    duration_weeks INT DEFAULT 12,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentee_requests (mentee_id, status),
    INDEX idx_mentor_requests (mentor_id, status),
    INDEX idx_status (status)
);

-- Active mentorships table (accepted mentorship relationships)
CREATE TABLE mentorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNIQUE NOT NULL,
    mentee_id INT NOT NULL,
    mentor_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    meeting_frequency ENUM('weekly', 'bi_weekly', 'monthly') DEFAULT 'weekly',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES mentorship_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentee_active (mentee_id, status),
    INDEX idx_mentor_active (mentor_id, status)
);

-- Messages table (messaging system)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentorship_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentorship_id) REFERENCES mentorships(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_mentorship_messages (mentorship_id, created_at),
    INDEX idx_receiver_unread (receiver_id, is_read),
    INDEX idx_sender_messages (sender_id, created_at)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('mentorship_request', 'request_accepted', 'request_rejected', 'new_message', 'system_announcement') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT, -- ID of related entity (request_id, message_id, etc.)
    is_read BOOLEAN DEFAULT FALSE,
    is_email_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read, created_at),
    INDEX idx_notification_type (type, created_at)
);

-- Session management table
CREATE TABLE user_sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id, last_activity)
);

-- Admin logs table (for audit trail)
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50), -- users, mentorships, etc.
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_actions (admin_id, created_at),
    INDEX idx_action_type (action, created_at)
);

-- System settings table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default skills
INSERT INTO skills (name, category, description) VALUES
('Web Development', 'Technical', 'Frontend and backend web development'),
('Mobile Development', 'Technical', 'iOS and Android app development'),
('Data Science', 'Technical', 'Data analysis and machine learning'),
('UI/UX Design', 'Design', 'User interface and user experience design'),
('Project Management', 'Management', 'Planning and executing projects'),
('Leadership', 'Soft Skills', 'Leading teams and inspiring others'),
('Communication', 'Soft Skills', 'Effective verbal and written communication'),
('Research', 'Academic', 'Research methodologies and practices'),
('Public Speaking', 'Soft Skills', 'Presenting to audiences'),
('Entrepreneurship', 'Business', 'Starting and running businesses'),
('Marketing', 'Business', 'Digital and traditional marketing strategies'),
('Finance', 'Business', 'Financial planning and analysis');

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_mentees_per_mentor', '3', 'Maximum number of mentees a mentor can have'),
('max_mentors_per_mentee', '1', 'Maximum number of mentors a mentee can have'),
('default_mentorship_duration', '12', 'Default mentorship duration in weeks'),
('email_notifications_enabled', 'true', 'Enable email notifications'),
('registration_enabled', 'true', 'Allow new user registrations'),
('maintenance_mode', 'false', 'Enable maintenance mode');

-- Create views for common queries
CREATE VIEW active_mentorships_view AS
SELECT 
    m.*,
    mentor.first_name as mentor_first_name,
    mentor.last_name as mentor_last_name,
    mentor.email as mentor_email,
    mentee.first_name as mentee_first_name,
    mentee.last_name as mentee_last_name,
    mentee.email as mentee_email
FROM mentorships m
JOIN users mentor ON m.mentor_id = mentor.id
JOIN users mentee ON m.mentee_id = mentee.id
WHERE m.status = 'active';

CREATE VIEW mentor_stats_view AS
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    COUNT(m.id) as active_mentees,
    AVG(DATEDIFF(CURRENT_DATE, m.start_date)) as avg_mentorship_days
FROM users u
LEFT JOIN mentorships m ON u.id = m.mentor_id AND m.status = 'active'
WHERE u.role = 'mentor' AND u.status = 'active'
GROUP BY u.id;