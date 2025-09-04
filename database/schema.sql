-- St. Kizito Seminary Preparatory School - Report Card Generation System
-- Database Schema

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `st_kizito_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `st_kizito_db`;

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('superadmin', 'admin', 'teacher') NOT NULL,
  `stream_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `classes`
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `streams`
CREATE TABLE `streams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `class_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
);

-- Table structure for table `students`
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `lin` VARCHAR(20) UNIQUE,
  `stream_id` INT NOT NULL,
  `profile_photo_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`stream_id`) REFERENCES `streams`(`id`) ON DELETE CASCADE
);

-- Table structure for table `subjects`
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table structure for table `report_batch_settings`
CREATE TABLE `report_batch_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `year` YEAR NOT NULL,
  `term` ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
  `stream_id` INT NOT NULL,
  `status` ENUM('pending', 'calculated', 'published') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`stream_id`) REFERENCES `streams`(`id`) ON DELETE CASCADE
);

-- Table structure for table `scores`
CREATE TABLE `scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `batch_id` INT NOT NULL,
  `exam_type` ENUM('BOT', 'MOT', 'EOT') NOT NULL,
  `marks` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `report_batch_settings`(`id`) ON DELETE CASCADE
);

-- Table structure for table `student_report_summary`
CREATE TABLE `student_report_summary` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `batch_id` INT NOT NULL,
  `total_marks` INT,
  `aggregate_points` INT,
  `division` VARCHAR(20),
  `position_in_stream` INT,
  `class_teacher_remarks` TEXT,
  `headteacher_remarks` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `report_batch_settings`(`id`) ON DELETE CASCADE
);

-- Default data
INSERT INTO `users` (`first_name`, `last_name`, `username`, `password`, `role`) VALUES
('Super', 'Admin', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin'); -- password

INSERT INTO `classes` (`name`) VALUES ('P5'), ('P6'), ('P7');

INSERT INTO `subjects` (`name`) VALUES
('Mathematics'),
('English'),
('Science'),
('Social Studies'),
('Religious Education');
