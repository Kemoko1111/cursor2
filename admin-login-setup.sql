-- Admin Login Setup for Menteego
-- Run this script to create admin users

-- First, let's check if we have a user to promote to admin
-- Replace 'admin@example.com' with the email of the user you want to make admin

-- Option 1: Update existing user to admin
UPDATE users 
SET user_role = 'admin', 
    status = 'active',
    email_verified = 1
WHERE email = 'admin@example.com';

-- Option 2: Create a new admin user directly
-- Replace the values below with your desired admin credentials
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

-- Option 3: Create multiple admin users
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
) VALUES 
    ('System', 'Admin', 'system@menteego.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT', 'N/A', 'active', 1, NOW()),
    ('Platform', 'Manager', 'manager@menteego.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Management', 'N/A', 'active', 1, NOW());

-- Verify admin users were created
SELECT id, first_name, last_name, email, user_role, status, email_verified 
FROM users 
WHERE user_role = 'admin';