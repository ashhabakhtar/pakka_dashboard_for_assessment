CREATE DATABASE IF NOT EXISTS pakka_dash;
USE pakka_dash;

-- Designations (Job Titles)
CREATE TABLE IF NOT EXISTS designations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- Users (with system RBAC role and physical designation)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    system_role ENUM('Sewak', 'Sangrakshak', 'Utpadak') NOT NULL DEFAULT 'Utpadak',
    designation_id INT,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
);

-- Tasks
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    due_date DATE,
    assigned_to INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Role Profiles
CREATE TABLE IF NOT EXISTS role_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designation_id INT NOT NULL UNIQUE,
    profile_text TEXT,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE
);

-- Assessments
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    designation_id INT NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    assessor_id INT,
    assessment_data LONGTEXT,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY (designation_id, fiscal_year)
);

CREATE TABLE IF NOT EXISTS personal_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    designation_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    attribute_desc TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE CASCADE
);



-- ==========================================
-- SEED DATA
-- ==========================================

-- Seed Designations
INSERT IGNORE INTO designations (id, title) VALUES 
(1, 'IT Head'),
(2, 'Brand & Marketing Head'),
(3, 'HR Executive'),
(4, 'Sales Lead'),
(5, 'Production Member');

-- Seed Users (password for all is 'password123')
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi is 'password123'
INSERT IGNORE INTO users (id, name, email, password_hash, system_role, designation_id) VALUES 
(1, 'Admin Sewak', 'sewak@pakka.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sewak', 1),
(2, 'Lead Sangrakshak', 'lead@pakka.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sangrakshak', 2),
(3, 'Member Utpadak', 'member@pakka.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Utpadak', 3);

-- Seed Tasks
INSERT IGNORE INTO tasks (title, description, status, priority, due_date, assigned_to, created_by) VALUES 
('Setup Portal DB', 'Initialize the MySQL schema.', 'Completed', 'High', '2026-06-01', 1, 1),
('Review Marketing Assets', 'Review new branding guidelines for Q3.', 'In Progress', 'Medium', '2026-06-15', 2, 1),
('Update Employee Handbook', 'Draft updates for the remote work policy.', 'Not Started', 'Low', '2026-06-20', 3, 1);

-- Seed Role Profiles
INSERT IGNORE INTO role_profiles (designation_id, profile_text) VALUES 
(1, 'IT Head is responsible for overseeing all technical infrastructure, managing the IT team, and ensuring the security and availability of enterprise systems.'),
(2, 'Brand & Marketing Head leads the strategy for public relations, product marketing, and brand identity globally.');

-- Seed Assessments
INSERT IGNORE INTO assessments (designation_id, fiscal_year, status, assessor_id) VALUES 
(1, 'FY 2026-27', 'Not Started', 1),
(2, 'FY 2026-27', 'In Progress', 1);
