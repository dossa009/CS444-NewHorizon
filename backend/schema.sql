-- New Horizon Database Schema
-- CIS 444 - Group 8
-- Based on ERD diagram with authentication support

-- Drop tables in correct order (dependencies first)
DROP TABLE IF EXISTS Discussion;
DROP TABLE IF EXISTS Calendar;
DROP TABLE IF EXISTS About;
DROP TABLE IF EXISTS Exercises;
DROP TABLE IF EXISTS Resources;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Webpages;

-- ============================================
-- WEBPAGES TABLE
-- ============================================
CREATE TABLE Webpages (
    Webpage_ID INT PRIMARY KEY AUTO_INCREMENT,
    Handle VARCHAR(100) NOT NULL UNIQUE,
    Title VARCHAR(255) NOT NULL,
    Webpage_URL VARCHAR(500)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE Users (
    User_ID INT PRIMARY KEY AUTO_INCREMENT,
    First_Name VARCHAR(100) NOT NULL,
    Last_Name VARCHAR(100) NOT NULL,
    Account_Type ENUM('user', 'admin') DEFAULT 'user',
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Country VARCHAR(100),
    State VARCHAR(100),
    Address VARCHAR(255),
    Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP,
    Last_Login DATETIME NULL,
    Is_Active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (Email),
    INDEX idx_account_type (Account_Type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RESOURCES TABLE
-- ============================================
CREATE TABLE Resources (
    Resource_ID INT PRIMARY KEY AUTO_INCREMENT,
    Webpage_ID INT,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Resource_URL VARCHAR(500),
    FOREIGN KEY (Webpage_ID) REFERENCES Webpages(Webpage_ID) ON DELETE SET NULL,
    INDEX idx_webpage (Webpage_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EXERCISES TABLE
-- ============================================
CREATE TABLE Exercises (
    Exercise_ID INT PRIMARY KEY AUTO_INCREMENT,
    Webpage_ID INT,
    Name VARCHAR(255) NOT NULL,
    Description TEXT,
    Exercise_URL VARCHAR(500),
    FOREIGN KEY (Webpage_ID) REFERENCES Webpages(Webpage_ID) ON DELETE SET NULL,
    INDEX idx_webpage (Webpage_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ABOUT TABLE
-- ============================================
CREATE TABLE About (
    About_ID INT PRIMARY KEY AUTO_INCREMENT,
    Webpage_ID INT,
    Last_Updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Webpage_ID) REFERENCES Webpages(Webpage_ID) ON DELETE SET NULL,
    INDEX idx_webpage (Webpage_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DISCUSSION TABLE
-- ============================================
CREATE TABLE Discussion (
    Discussion_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT,
    Discussion_Title VARCHAR(255) NOT NULL,
    Date_Created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_user (User_ID),
    INDEX idx_created (Date_Created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CALENDAR TABLE
-- ============================================
CREATE TABLE Calendar (
    Event_ID INT PRIMARY KEY AUTO_INCREMENT,
    User_ID INT,
    URL VARCHAR(500),
    Date DATE NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_user (User_ID),
    INDEX idx_date (Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert webpages
INSERT INTO Webpages (Handle, Title, Webpage_URL) VALUES
('home', 'Home', '/index.html'),
('resources', 'Resources', '/pages/resources.html'),
('exercises', 'Exercises', '/pages/exercises.html'),
('calendar', 'Calendar', '/pages/calendar.html'),
('forum', 'Forum', '/pages/forum.html'),
('about', 'About', '/pages/about.html');

-- Insert default admin user (password: Admin123!)
INSERT INTO Users (First_Name, Last_Name, Email, Password_Hash, Account_Type) VALUES
('Admin', 'User', 'admin@newhorizon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample users (password: password for all)
INSERT INTO Users (First_Name, Last_Name, Email, Password_Hash, Account_Type, Country, State) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'USA', 'California'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'USA', 'Texas'),
('Mike', 'Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'USA', 'New York');

-- Insert sample resources
INSERT INTO Resources (Webpage_ID, Title, Description, Resource_URL) VALUES
(2, 'Mental Health Guide', 'Comprehensive guide for mental wellness', 'https://example.com/mental-health'),
(2, 'Stress Management Tips', 'Learn how to manage stress effectively', 'https://example.com/stress'),
(2, 'Mindfulness Basics', 'Introduction to mindfulness meditation', 'https://example.com/mindfulness');

-- Insert sample exercises
INSERT INTO Exercises (Webpage_ID, Name, Description, Exercise_URL) VALUES
(3, 'Morning Meditation', '10-minute morning meditation routine', 'https://example.com/morning-med'),
(3, 'Deep Breathing', 'Breathing exercises for relaxation', 'https://example.com/breathing'),
(3, 'Body Scan', 'Full body relaxation technique', 'https://example.com/body-scan');

-- Insert sample calendar events
INSERT INTO Calendar (User_ID, URL, Date) VALUES
(1, 'https://example.com/event1', CURDATE()),
(1, 'https://example.com/event2', DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
(2, 'https://example.com/event3', DATE_ADD(CURDATE(), INTERVAL 14 DAY));

-- Insert sample discussions
INSERT INTO Discussion (User_ID, Discussion_Title) VALUES
(2, 'Tips for beginners'),
(3, 'My meditation journey'),
(4, 'Best time to meditate?');
