-- SQL command to add the date columns to the 'report_batch_settings' table.
-- Please run this query in your phpMyAdmin to update the database structure.

ALTER TABLE `report_batch_settings`
ADD COLUMN `term_end_date` DATE NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `next_term_begin_date` DATE NULL DEFAULT NULL AFTER `term_end_date`;
