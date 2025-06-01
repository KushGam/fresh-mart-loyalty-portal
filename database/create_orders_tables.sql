-- Drop existing tables if they exist
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;

-- Create Orders Table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `delivery_time` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create Order Items Table
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints after tables are created
ALTER TABLE `orders` 
ADD CONSTRAINT `orders_ibfk_1` 
FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `order_items` 
ADD CONSTRAINT `order_items_ibfk_1` 
FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

ALTER TABLE `order_items` 
ADD CONSTRAINT `order_items_ibfk_2` 
FOREIGN KEY (`product_id`) REFERENCES `products` (`id`); 