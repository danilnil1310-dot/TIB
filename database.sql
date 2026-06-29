-- Buat database futsal_booking
CREATE DATABASE IF NOT EXISTS futsal_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE futsal_booking;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lapangan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  lapangan_id INT NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  duration INT NOT NULL,
  total_price DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL DEFAULT 'qris',
  booking_status ENUM('pending','confirmed','canceled') NOT NULL DEFAULT 'pending',
  payment_status ENUM('menunggu_pembayaran','berhasil','gagal','kadaluarsa') NOT NULL DEFAULT 'menunggu_pembayaran',
  payment_expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE
);

INSERT IGNORE INTO users (name, username, password, role) VALUES
('Admin Futsal', 'admin', '$2y$10$WFrj1RXGeR.0Fnl69Al0xeN8Y3/bAjEHZL8MQFm2kLB0bk7LTG7/q', 'admin');

INSERT IGNORE INTO lapangan (name, price, description, image) VALUES
('Lapangan A', 150000, 'Lapangan futsal standar 5 orang', 'https://images.unsplash.com/photo-1505842465776-3bd2144b5caa?auto=format&fit=crop&w=800&q=80'),
('Lapangan B', 170000, 'Lapangan futsal premium dengan lighting', 'https://images.unsplash.com/photo-1542736667-069246bdbc82?auto=format&fit=crop&w=800&q=80');
