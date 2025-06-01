-- Drop existing order_promotions table if it exists
DROP TABLE IF EXISTS order_promotions;

-- Create order_promotions table with proper structure
CREATE TABLE order_promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    promotion_id INT NOT NULL,
    user_id BIGINT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    discount_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add indexes for better performance
CREATE INDEX idx_order_promotions_user ON order_promotions(user_id, promotion_id);
CREATE INDEX idx_order_promotions_order ON order_promotions(order_id);

-- Modify promotions table to ensure proper structure
ALTER TABLE promotions
MODIFY COLUMN usage_limit INT DEFAULT NULL COMMENT 'Number of times each customer can use this promotion (NULL or 0 for unlimited)',
ADD COLUMN is_redeemable BOOLEAN DEFAULT TRUE COMMENT 'Whether the promotion can be redeemed by customers';

-- Update existing promotions to be redeemable
UPDATE promotions SET is_redeemable = TRUE WHERE is_active = 1; 