-- First, remove duplicate entries keeping only the latest record for each user
DELETE cd1 FROM customer_details cd1
INNER JOIN customer_details cd2
WHERE cd1.user_id = cd2.user_id
AND cd1.id < cd2.id;

-- Then add the unique constraint
ALTER TABLE customer_details
ADD UNIQUE INDEX unique_user_id (user_id); 