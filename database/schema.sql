-- HaatKatha Database Schema
-- Run this in phpMyAdmin or: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS haatkatha CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE haatkatha;

-- ---------------------------------------------------------------
-- users: one table for all three roles (artisan / customer / admin)
-- ---------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('artisan', 'customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------------
-- artisans: extra profile info, one row per artisan user
-- ---------------------------------------------------------------
CREATE TABLE artisans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    craft_type VARCHAR(100),
    location VARCHAR(100),
    experience_years INT,
    story TEXT,
    photo VARCHAR(255),
    approved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------
-- categories
-- ---------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (name) VALUES
    ('Handloom'), ('Bamboo & Cane'), ('Jewellery'), ('Pottery'),
    ('Silk (Muga/Eri/Pat)'), ('Woodcraft'), ('Others');

-- ---------------------------------------------------------------
-- products
-- ---------------------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    artisan_id INT NOT NULL,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    material VARCHAR(150),
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT,
    description_lang ENUM('en','as') NOT NULL DEFAULT 'en',
    image VARCHAR(255),
    keywords VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES artisans(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------
-- enquiries: basic "order request" from a customer to an artisan
-- ---------------------------------------------------------------
CREATE TABLE enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_contact VARCHAR(150) NOT NULL,
    message TEXT,
    status ENUM('new', 'seen', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------
-- ai_logs: optional record of AI requests (per synopsis section 13)
-- ---------------------------------------------------------------
CREATE TABLE ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type ENUM('description', 'translation', 'keywords') NOT NULL,
    input_text TEXT,
    output_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------
-- Seed admin account (email: admin@haatkatha.local / password: Admin@123)
-- Password hash below is for 'Admin@123' — change it after first login.
-- ---------------------------------------------------------------
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@haatkatha.local', '$2b$10$oCcfsGujegS9pdHJu9xW5O1gF5hVuFakiEmlNfpIIiXQDUw6tYWHe', 'admin');
