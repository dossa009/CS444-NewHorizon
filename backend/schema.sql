-- New Horizon Database Schema - Simplified
-- Only Users and Admin functionality

DROP TABLE IF EXISTS Admin_Audit_Log;
DROP TABLE IF EXISTS Users;

-- Users table (only 'user' and 'admin' roles)
CREATE TABLE Users (
    User_ID INT PRIMARY KEY AUTO_INCREMENT,
    First_Name VARCHAR(100) NOT NULL,
    Last_Name VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Account_Type ENUM('user', 'admin') DEFAULT 'user',
    Is_Active BOOLEAN DEFAULT TRUE,
    Date_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Last_Login TIMESTAMP NULL,
    Profile_Picture_URL VARCHAR(500),
    Bio TEXT,
    INDEX idx_email (Email),
    INDEX idx_account_type (Account_Type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Audit Log
CREATE TABLE Admin_Audit_Log (
    Log_ID INT PRIMARY KEY AUTO_INCREMENT,
    Admin_ID INT NOT NULL,
    Action VARCHAR(255) NOT NULL,
    Target_Type VARCHAR(50),
    Target_ID INT,
    Details TEXT,
    IP_Address VARCHAR(45),
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Admin_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    INDEX idx_admin (Admin_ID),
    INDEX idx_action (Action),
    INDEX idx_created (Created_At)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (email: admin@newhorizon.com, password: Admin123!)
INSERT INTO Users (First_Name, Last_Name, Email, Password_Hash, Account_Type)
VALUES (
    'Admin',
    'User',
    'admin@newhorizon.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- Insert sample regular users for testing
INSERT INTO Users (First_Name, Last_Name, Email, Password_Hash, Account_Type) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Mike', 'Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Resources table
DROP TABLE IF EXISTS Resources;
CREATE TABLE Resources (
    Resource_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Content LONGTEXT,
    Category VARCHAR(100),
    Tags JSON,
    External_URL VARCHAR(500),
    Views_Count INT DEFAULT 0,
    Is_Published BOOLEAN DEFAULT FALSE,
    Created_By INT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_category (Category),
    INDEX idx_published (Is_Published),
    INDEX idx_created (Created_At)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forum Posts table
DROP TABLE IF EXISTS Forum_Comments;
DROP TABLE IF EXISTS Forum_Posts;
CREATE TABLE Forum_Posts (
    Post_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Content LONGTEXT NOT NULL,
    Author_ID INT,
    Status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    Views_Count INT DEFAULT 0,
    Is_Pinned BOOLEAN DEFAULT FALSE,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Author_ID) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_status (Status),
    INDEX idx_author (Author_ID),
    INDEX idx_created (Created_At)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Forum Comments table
CREATE TABLE Forum_Comments (
    Comment_ID INT PRIMARY KEY AUTO_INCREMENT,
    Post_ID INT NOT NULL,
    Author_ID INT,
    Content TEXT NOT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Post_ID) REFERENCES Forum_Posts(Post_ID) ON DELETE CASCADE,
    FOREIGN KEY (Author_ID) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_post (Post_ID),
    INDEX idx_author (Author_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Calendar Events table
DROP TABLE IF EXISTS Event_Registrations;
DROP TABLE IF EXISTS Calendar_Events;
CREATE TABLE Calendar_Events (
    Event_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Event_Date DATE NOT NULL,
    Start_Time TIME,
    End_Time TIME,
    Event_Type VARCHAR(100),
    Location VARCHAR(255),
    Is_Online BOOLEAN DEFAULT FALSE,
    Max_Participants INT,
    Is_Published BOOLEAN DEFAULT FALSE,
    Created_By INT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_date (Event_Date),
    INDEX idx_published (Is_Published),
    INDEX idx_type (Event_Type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event Registrations table
CREATE TABLE Event_Registrations (
    Registration_ID INT PRIMARY KEY AUTO_INCREMENT,
    Event_ID INT NOT NULL,
    User_ID INT NOT NULL,
    Registered_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Event_ID) REFERENCES Calendar_Events(Event_ID) ON DELETE CASCADE,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (Event_ID, User_ID),
    INDEX idx_event (Event_ID),
    INDEX idx_user (User_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercises table
DROP TABLE IF EXISTS Exercise_Completions;
DROP TABLE IF EXISTS Exercises;
CREATE TABLE Exercises (
    Exercise_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Instructions LONGTEXT,
    Exercise_Type VARCHAR(100),
    Difficulty ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    Duration_Minutes INT,
    Is_Published BOOLEAN DEFAULT FALSE,
    Created_By INT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_type (Exercise_Type),
    INDEX idx_difficulty (Difficulty),
    INDEX idx_published (Is_Published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercise Completions table
CREATE TABLE Exercise_Completions (
    Completion_ID INT PRIMARY KEY AUTO_INCREMENT,
    Exercise_ID INT NOT NULL,
    User_ID INT NOT NULL,
    Completed_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Notes TEXT,
    FOREIGN KEY (Exercise_ID) REFERENCES Exercises(Exercise_ID) ON DELETE CASCADE,
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE,
    INDEX idx_exercise (Exercise_ID),
    INDEX idx_user (User_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opportunities table
DROP TABLE IF EXISTS Opportunities;
CREATE TABLE Opportunities (
    Opportunity_ID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    Opportunity_Type VARCHAR(100),
    Organization VARCHAR(255),
    Location VARCHAR(255),
    Is_Remote BOOLEAN DEFAULT FALSE,
    Contact_Email VARCHAR(255),
    Apply_URL VARCHAR(500),
    Is_Published BOOLEAN DEFAULT FALSE,
    Created_By INT,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID) ON DELETE SET NULL,
    INDEX idx_type (Opportunity_Type),
    INDEX idx_published (Is_Published),
    INDEX idx_remote (Is_Remote)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Messages table
DROP TABLE IF EXISTS Contact_Messages;
CREATE TABLE Contact_Messages (
    Message_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    Subject VARCHAR(255),
    Message TEXT NOT NULL,
    Status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (Status),
    INDEX idx_created (Created_At)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
