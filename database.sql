/* ==========================================================
   PORTFOLIO GENERATOR - DATABASE STRUCTURE
   ========================================================== */

-- Create Database
CREATE DATABASE IF NOT EXISTS portfolio_gen;
USE portfolio_gen;

-- ==========================================================
--  1. USERS TABLE - Authentication & Login
-- ==========================================================
-- Stores user credentials and basic account information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================================
--  2. PORTFOLIOS TABLE - Portfolio Data
-- ==========================================================
-- Stores all portfolio information (personal info, bio, contact)
CREATE TABLE portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_title VARCHAR(100),
    profile_photo_url TEXT,
    location VARCHAR(80),
    website_url TEXT,
    bio_short VARCHAR(120),
    bio_long LONGTEXT,
    years_exp VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
--  3. SKILLS TABLE - Technical Skills & Proficiency
-- ==========================================================
-- Stores user skills with proficiency levels (0-100)
CREATE TABLE skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    skill_name VARCHAR(50) NOT NULL,
    proficiency_level INT DEFAULT 0 CHECK (proficiency_level >= 0 AND proficiency_level <= 100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
--  4. PROJECTS TABLE - Project Showcase
-- ==========================================================
-- Stores user projects with descriptions and links
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    project_title VARCHAR(100) NOT NULL,
    project_description LONGTEXT,
    project_url TEXT,
    project_image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
--  5. SOCIAL_LINKS TABLE - Social Media & Contact
-- ==========================================================
-- Stores social media profiles and contact links
CREATE TABLE social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    platform_name VARCHAR(30), -- github, linkedin, twitter, instagram
    profile_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
--  INDEXES - Performance Optimization
-- ==========================================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_portfolios_user_id ON portfolios(user_id);
CREATE INDEX idx_skills_portfolio_id ON skills(portfolio_id);
CREATE INDEX idx_projects_portfolio_id ON projects(portfolio_id);
CREATE INDEX idx_social_links_portfolio_id ON social_links(portfolio_id);

/* ========================================================== */
