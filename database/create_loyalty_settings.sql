-- Create loyalty settings table
CREATE TABLE IF NOT EXISTS `loyalty_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `points_per_dollar` decimal(10,2) NOT NULL DEFAULT '1.00',
  `min_points_redeem` int(11) NOT NULL DEFAULT '100',
  `points_to_amount` int(11) NOT NULL DEFAULT '100',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings
INSERT INTO `loyalty_settings` (`id`, `points_per_dollar`, `min_points_redeem`, `points_to_amount`) 
VALUES (1, 1.00, 100, 100)
ON DUPLICATE KEY UPDATE 
  points_per_dollar = VALUES(points_per_dollar),
  min_points_redeem = VALUES(min_points_redeem),
  points_to_amount = VALUES(points_to_amount); 