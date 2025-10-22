-- Create Admin User for Menteego Platform
-- Run this script to create an admin user in your existing database

-- Option 1: Create a new admin user
INSERT INTO users (
    email,
    password_hash,
    first_name,
    last_name,
    student_id,
    phone,
    year_of_study,
    department,
    role,
    status,
    email_verified,
    created_at
) VALUES (
    'admin@menteego.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'Admin',
    'User',
    'ADMIN001',
    '+1234567890',
    'faculty',
    'Administration',
    'admin',
    'active',
    1,
    NOW()
);

-- Option 2: Update existing user to admin (replace 'user@example.com' with actual email)
-- UPDATE users 
-- SET role = 'admin', 
--     status = 'active',
--     email_verified = 1
-- WHERE email = 'user@example.com';

-- Option 3: Create multiple admin users
INSERT INTO users (
    email, password_hash, first_name, last_name, student_id, phone, year_of_study, department, role, status, email_verified, created_at
) VALUES 
    ('system@menteego.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Admin', 'ADMIN002', '+1234567891', 'faculty', 'IT', 'admin', 'active', 1, NOW()),
    ('manager@menteego.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Platform', 'Manager', 'ADMIN003', '+1234567892', 'faculty', 'Management', 'admin', 'active', 1, NOW());

-- Verify admin users were created
SELECT id, first_name, last_name, email, role, status, email_verified, created_at 
FROM users 
WHERE role = 'admin'
ORDER BY created_at DESC;

-- Show admin login credentials
SELECT 
    CONCAT('Email: ', email) as login_info,
    CONCAT('Password: password') as password_info,
    CONCAT('Role: ', role) as role_info
FROM users 
WHERE role = 'admin';