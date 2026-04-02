-- ExpenseFlow Database Schema
CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    avatar VARCHAR(255) DEFAULT NULL,
    monthly_income DECIMAL(12,2) DEFAULT 0.00,
    email_reminders TINYINT(1) DEFAULT 1,
    reminder_day INT DEFAULT 28,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'tag',
    color VARCHAR(20) DEFAULT '#6366f1',
    type ENUM('expense','income') DEFAULT 'expense',
    is_default TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('expense','income') NOT NULL,
    date DATE NOT NULL,
    note TEXT,
    payment_method VARCHAR(50) DEFAULT 'cash',
    tags VARCHAR(255),
    is_recurring TINYINT(1) DEFAULT 0,
    recurring_interval VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    period ENUM('weekly','monthly','yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    alert_threshold INT DEFAULT 80,
    color VARCHAR(20) DEFAULT '#6366f1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS savings_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    current_amount DECIMAL(12,2) DEFAULT 0.00,
    deadline DATE,
    icon VARCHAR(50) DEFAULT 'piggy-bank',
    color VARCHAR(20) DEFAULT '#10b981',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
