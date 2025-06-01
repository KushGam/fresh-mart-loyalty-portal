-- Create personalized offers table
CREATE TABLE IF NOT EXISTS `personalized_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `offer_type` enum('birthday', 'loyalty_tier', 'special', 'seasonal') NOT NULL,
  `points_multiplier` decimal(10,2) DEFAULT 1.00,
  `minimum_purchase` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('percentage', 'fixed') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `loyalty_tier` enum('bronze', 'silver', 'gold', 'platinum') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create loyalty tiers table
CREATE TABLE IF NOT EXISTS `loyalty_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tier_name` enum('bronze', 'silver', 'gold', 'platinum') NOT NULL,
  `points_required` int(11) NOT NULL,
  `points_multiplier` decimal(10,2) DEFAULT 1.00,
  `special_benefits` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default loyalty tiers
INSERT INTO `loyalty_tiers` (`tier_name`, `points_required`, `points_multiplier`, `special_benefits`) VALUES
('bronze', 0, 1.00, 'Basic membership benefits'),
('silver', 5000, 1.25, 'Birthday bonus points, Exclusive seasonal offers'),
('gold', 10000, 1.50, 'Birthday bonus points, Exclusive seasonal offers, Double points days'),
('platinum', 20000, 2.00, 'Birthday bonus points, Exclusive seasonal offers, Double points days, Priority support')
ON DUPLICATE KEY UPDATE
  points_multiplier = VALUES(points_multiplier),
  special_benefits = VALUES(special_benefits);

-- Create user offers table (to track which offers are assigned to users)
CREATE TABLE IF NOT EXISTS `user_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `offer_id` (`offer_id`),
  CONSTRAINT `user_offers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_offers_ibfk_2` FOREIGN KEY (`offer_id`) REFERENCES `personalized_offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add birthday field to customer_details table if it doesn't exist
ALTER TABLE `customer_details` 
ADD COLUMN IF NOT EXISTS `birthday` date DEFAULT NULL AFTER `address`,
ADD COLUMN IF NOT EXISTS `loyalty_tier` enum('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze' AFTER `birthday`;

-- Create trigger to automatically update loyalty tier based on points
DELIMITER //
CREATE TRIGGER update_loyalty_tier AFTER UPDATE ON rewards
FOR EACH ROW
BEGIN
    DECLARE bronze_points INT;
    DECLARE silver_points INT;
    DECLARE gold_points INT;
    DECLARE platinum_points INT;
    
    -- Get points required for each tier
    SELECT points_required INTO bronze_points FROM loyalty_tiers WHERE tier_name = 'bronze' LIMIT 1;
    SELECT points_required INTO silver_points FROM loyalty_tiers WHERE tier_name = 'silver' LIMIT 1;
    SELECT points_required INTO gold_points FROM loyalty_tiers WHERE tier_name = 'gold' LIMIT 1;
    SELECT points_required INTO platinum_points FROM loyalty_tiers WHERE tier_name = 'platinum' LIMIT 1;
    
    -- Update customer_details with new tier based on total points
    UPDATE customer_details 
    SET loyalty_tier = CASE
        WHEN NEW.points >= platinum_points THEN 'platinum'
        WHEN NEW.points >= gold_points THEN 'gold'
        WHEN NEW.points >= silver_points THEN 'silver'
        ELSE 'bronze'
    END
    WHERE user_id = NEW.user_id;
END;//
DELIMITER ; 