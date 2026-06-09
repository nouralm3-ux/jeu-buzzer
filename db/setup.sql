CREATE DATABASE IF NOT EXISTS sensor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sensor_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sensors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    port VARCHAR(20) NOT NULL,
    baud_rate INT NOT NULL DEFAULT 9600,
    description TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sensor_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_id INT NOT NULL,
    raw_value TEXT,
    numeric_value FLOAT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sensor_id) REFERENCES sensors(id) ON DELETE CASCADE
);

-- Default admin user: admin / admin123
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$x1fXgvsf78dctWfmkZ10KeESNk7HY8Ii0qoX0qW3czTRZCIWV/sdG');
