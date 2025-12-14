-- Add `pin` column to password_resets table (run once)
ALTER TABLE `password_resets`
  ADD COLUMN `pin` VARCHAR(10) DEFAULT NULL AFTER `token`;

-- Optional: add index for quick lookups by user_id and pin
CREATE INDEX idx_password_resets_user_pin ON password_resets(user_id, pin);
