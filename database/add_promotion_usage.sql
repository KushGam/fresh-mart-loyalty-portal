-- Create table for tracking per-customer promotion usage
CREATE TABLE IF NOT EXISTS promotion_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    promotion_id INT NOT NULL,
    order_id INT NOT NULL,
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_promo (user_id, promotion_id, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for faster lookups
CREATE INDEX idx_promotion_usage ON promotion_usage (user_id, promotion_id);

-- Update the promotions table description
ALTER TABLE promotions 
MODIFY COLUMN usage_limit INT DEFAULT NULL COMMENT 'Number of times each customer can use this promotion (NULL for unlimited)',
MODIFY COLUMN usage_count INT DEFAULT 0 COMMENT 'Total number of times this promotion has been used across all customers'; 