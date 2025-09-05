-- SQL command to add the 'teacher_initials' column to the 'subjects' table.
-- Please run this query in your phpMyAdmin to update the database structure.

ALTER TABLE `subjects`
ADD COLUMN `teacher_initials` VARCHAR(10) NULL DEFAULT NULL AFTER `name`;
