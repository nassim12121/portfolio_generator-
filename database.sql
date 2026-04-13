/* ==========================================================
   PORTFOLIO GENERATOR - DATABASE STRUCTURE
========================================================== */

-- Create Database
CREATE DATABASE IF NOT EXISTS portfolio_gen;
USE portfolio_gen;

-- Clean existing schema (in the correct database)
DROP TABLE IF EXISTS social_links;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS portfolios;
DROP TABLE IF EXISTS users;

-- ==========================================================
-- 1. USERS TABLE - Authentication & Login
-- ==========================================================
-- Stores user credentials and basic account information
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(50)  NOT NULL,
  last_name  VARCHAR(50)  NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================================
-- 2. PORTFOLIOS TABLE - Portfolio Data
-- ==========================================================
-- Stores all portfolio information (personal info, bio, contact)
CREATE TABLE IF NOT EXISTS portfolios (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT          NOT NULL,
  portfolio_title  VARCHAR(120),
  slug             VARCHAR(140) NOT NULL UNIQUE,
  is_public        TINYINT(1) NOT NULL DEFAULT 0,
  theme_name       VARCHAR(30) NOT NULL DEFAULT 'aurora',
  job_title        VARCHAR(100),
  profile_photo_url TEXT,
  location         VARCHAR(80),
  website_url      TEXT,
  bio_short        VARCHAR(120),
  bio_long         LONGTEXT,
  years_exp        VARCHAR(20),
  phone            VARCHAR(20),
  email            VARCHAR(100),
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================================
-- 3. SKILLS TABLE - Technical Skills & Proficiency
-- ==========================================================
-- Stores user skills with proficiency levels (0-100)
CREATE TABLE IF NOT EXISTS skills (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  portfolio_id     INT NOT NULL,
  skill_name       VARCHAR(50) NOT NULL,
  proficiency_level INT DEFAULT 0 CHECK (proficiency_level >= 0 AND proficiency_level <= 100),
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
-- 4. PROJECTS TABLE - Project Showcase
-- ==========================================================
-- Stores user projects with descriptions, links and tags
CREATE TABLE IF NOT EXISTS projects (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  portfolio_id        INT NOT NULL,
  display_order       INT NOT NULL DEFAULT 0,
  project_title       VARCHAR(100) NOT NULL,
  project_description LONGTEXT,
  project_url         TEXT,
  project_image_url   TEXT,
  repo_url            TEXT,                  -- ✅ added: GitHub / source code URL
  tags                VARCHAR(200),          -- ✅ added: comma-separated tech tags
  is_featured         TINYINT(1) NOT NULL DEFAULT 0,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
-- 5. SOCIAL_LINKS TABLE - Social Media & Contact
-- ==========================================================
-- Stores social media profiles and contact links
CREATE TABLE IF NOT EXISTS social_links (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  portfolio_id  INT NOT NULL,
  platform_name VARCHAR(30),  -- github, linkedin, twitter, instagram
  profile_url   TEXT NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
);

-- ==========================================================
-- INDEXES - Performance Optimization
-- ==========================================================
-- NOTE:
-- `users.email` is already indexed by UNIQUE constraint.
-- Foreign key columns are automatically indexed by InnoDB when needed.
-- Keeping this section empty avoids duplicate-index errors when re-running this script.