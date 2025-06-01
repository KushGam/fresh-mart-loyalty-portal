-- Create offer_redemptions table
CREATE TABLE IF NOT EXISTS offer_redemptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    redemption_date DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (offer_id) REFERENCES personalized_offers(id)
);

-- Add birth_date field to personalized_offers table if not exists
ALTER TABLE personalized_offers
ADD COLUMN birth_date VARCHAR(5) NULL COMMENT 'Birth date in MM-DD format for birthday offers';

-- Add birthday field to customer_details table if not exists
ALTER TABLE customer_details
ADD COLUMN birthday DATE NULL COMMENT 'Customer birthday for birthday offers';

-- Add index for faster birthday offer lookups
CREATE INDEX idx_birth_date ON personalized_offers(birth_date);
CREATE INDEX idx_customer_birthday ON customer_details(birthday);

-- Sample birthday offer
INSERT INTO personalized_offers (
    title,
    description,
    offer_type,
    points_multiplier,
    minimum_purchase,
    discount_amount,
    discount_type,
    start_date,
    end_date,
    birth_date,
    is_active
) VALUES (
    'Birthday Special: 20% Off Your Purchase!',
    'Celebrate your birthday with us! Get 20% off your entire purchase.',
    'birthday',
    2.0,
    0.00,
    20.00,
    'percentage',
    '2024-01-01',
    '2024-12-31',
    NULL,
    1
); 