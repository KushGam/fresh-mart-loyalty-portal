ALTER TABLE personalized_offers
ADD COLUMN usage_limit INT DEFAULT NULL COMMENT 'Number of times a customer can use this offer (NULL for unlimited)'; 