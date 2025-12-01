-- Migration: Add siblings table for F2 Personal Data Form
-- Date: 2025-01-11
-- Description: Creates a separate table to store multiple siblings entries

-- Create siblings table
CREATE TABLE IF NOT EXISTS `f2_siblings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `sibling_name` varchar(255) NOT NULL,
  `birth_order` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_siblings_student_id` (`student_id`),
  CONSTRAINT `fk_siblings_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Show the new table structure
DESCRIBE `f2_siblings`;
