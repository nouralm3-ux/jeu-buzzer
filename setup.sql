CREATE TABLE IF NOT EXISTS g8b_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE g8b_etat_actuel (
    id INT PRIMARY KEY,
    etat INT,
    last_change DATETIME
);

-- Default admin user: admin / admin123
INSERT INTO g8b_users (username, password_hash) VALUES
('admin', '$2y$10$x1fXgvsf78dctWfmkZ10KeESNk7HY8Ii0qoX0qW3czTRZCIWV/sdG');

INSERT INTO g8b_etat_actuel (id, etat) VALUES (1, 0);