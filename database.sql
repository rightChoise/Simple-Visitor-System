-- Optional: the app creates all this automatically on first run.
-- Import manually only if you prefer phpMyAdmin.
CREATE DATABASE IF NOT EXISTS visitor_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE visitor_system;

CREATE TABLE IF NOT EXISTS visitors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mobile VARCHAR(15) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  photo VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_id INT UNSIGNED NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visits_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
);
