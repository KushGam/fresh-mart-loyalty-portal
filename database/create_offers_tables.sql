-- Create personalized_offers table
CREATE TABLE IF NOT EXISTS personalized_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    offer_type ENUM('regular', 'birthday', 'welcome') NOT NULL DEFAULT 'regular',
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    minimum_purchase DECIMAL(10,2) DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    loyalty_tier VARCHAR(50),
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    birthday_dates VARCHAR(5) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_offers table
CREATE TABLE IF NOT EXISTS user_offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    status ENUM('active', 'used', 'expired') NOT NULL DEFAULT 'active',
    redemption_date TIMESTAMP NOT NULL,
    used_date TIMESTAMP NULL DEFAULT NULL,
    order_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (offer_id) REFERENCES personalized_offers(id)
);

-- Create store_redemption table if not exists
CREATE TABLE IF NOT EXISTS store_redemption (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    store_id INT NOT NULL,
    points_used INT NOT NULL,
    redemption_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
); 