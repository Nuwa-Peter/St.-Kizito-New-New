-- SQL command to add the 'other_name' column to the 'students' table.
-- Please run this query in your phpMyAdmin to update the database structure.

ALTER TABLE `students`
ADD COLUMN `other_name` VARCHAR(50) NULL DEFAULT NULL AFTER `last_name`;
