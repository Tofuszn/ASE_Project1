CREATE DATABASE IF NOT EXISTS dealership;
USE dealership;

CREATE TABLE IF NOT EXISTS cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  make VARCHAR(50) NOT NULL,
  model VARCHAR(50) NOT NULL,
  year INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  status ENUM('available','sold') DEFAULT 'available'
);

CREATE TABLE IF NOT EXISTS sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  car_id INT NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  sale_price DECIMAL(10,2) NOT NULL,
  date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','salesperson') DEFAULT 'salesperson',
  token VARCHAR(255) DEFAULT NULL
);

-- Sample admin user (replace the hash with one generated via PHP password_hash)
-- INSERT INTO staff (username, password_hash, role)
-- VALUES ('admin', '$2y$12$kNikWCY32mb9fLQ1PN0MWuGMmi7bxxJj8UeGStmcQHuc06PFgYiCu', 'admin');
