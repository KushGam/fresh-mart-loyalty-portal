-- Add usage_count column to track how many times an offer has been used
ALTER TABLE personalized_offers ADD COLUMN usage_count INT DEFAULT 0;

-- Add index for better performance when checking usage limits
CREATE INDEX idx_offer_usage ON personalized_offers(id, usage_limit, usage_count); 