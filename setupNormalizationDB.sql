CREATE DATABASE IF NOT EXISTS normalizationdb;

USE normalizationdb;

-- Table for storing candidate information
CREATE TABLE IF NOT EXISTS candidates (
    candidate_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    date_of_birth VARCHAR(255) NOT NULL,
    gender VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    registration_date DATETIME NOT NULL
);

-- Table for storing phone numbers
CREATE TABLE IF NOT EXISTS phone_numbers (
    phone_id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE
);