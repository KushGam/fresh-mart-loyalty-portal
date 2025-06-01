-- Create promotions table
CREATE TABLE IF NOT EXISTS promotions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    banner_image VARCHAR(255),
    discount_amount DECIMAL(10,2),
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    minimum_purchase DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT true,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample promotions
INSERT INTO promotions (title, description, start_date, end_date, banner_image, discount_amount, discount_type, minimum_purchase, is_active, usage_limit, usage_count) VALUES
('Summer Sale Extravaganza', 'Get amazing discounts on all summer essentials!', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'Images Assets/summer_sale.jpeg', 20.00, 'percentage', 50.00, true, NULL, 0),
('Fresh Produce Week', 'Special discounts on all fresh fruits and vegetables', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), 'Images Assets/fresh_produce.jpeg', 15.00, 'percentage', 30.00, true, NULL, 0),
('Weekend Flash Sale', 'Don\'t miss out on these incredible weekend deals!', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 4 DAY), 'Images Assets/flash_sale.jpeg', 25.00, 'percentage', 100.00, true, NULL, 0),
('Buy More Save More', 'The more you buy, the more you save!', CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), 'Images Assets/save_more.jpeg', 50.00, 'fixed', 200.00, true, NULL, 0); 