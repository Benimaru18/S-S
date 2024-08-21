CREATE DATABASE user_management;

USE user_management;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE
);

ALTER TABLE users
ADD COLUMN first_name VARCHAR(50) AFTER email,
ADD COLUMN last_name VARCHAR(50) AFTER first_name;

ALTER TABLE users ADD COLUMN profile_image VARCHAR(255);

ALTER TABLE users ADD COLUMN join_date DATE DEFAULT CURRENT_DATE;


ALTER TABLE users
ADD COLUMN email_visibility ENUM('public', 'friends', 'private') DEFAULT 'private',
ADD COLUMN profile_visibility ENUM('public', 'friends', 'private') DEFAULT 'private',
ADD COLUMN two_factor_auth TINYINT(1) DEFAULT 0;

CREATE TABLE daily_quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    author_image VARCHAR(255) NOT NULL,
    author_bio TEXT,
    context TEXT,
    date DATE NOT NULL
);

INSERT INTO daily_quotes (quote, author, date) 
VALUES ('The only way to do great work is to love what you do.', 'Steve Jobs', CURDATE());


ALTER TABLE daily_quotes ADD COLUMN author_image VARCHAR(255);
ALTER TABLE daily_quotes
ADD COLUMN author_bio TEXT,
ADD COLUMN context TEXT;


SELECT * FROM users;

CREATE TABLE well_being_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    happiness INT NOT NULL,
    workload INT NOT NULL,
    anxiety INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE consultation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    preferred_date DATE NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
