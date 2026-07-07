-- Run in phpMyAdmin: https://auth-db1904.hstgr.io
-- Database: u274391035_db_BbBE85ay
-- User: u274391035_usr_BbBE85ay

-- sanad_limit column may already exist; ignore duplicate error if so
ALTER TABLE users ADD COLUMN sanad_limit DECIMAL(12,2) DEFAULT 0.00;

-- Reset all demo passwords to 123456 (generate fresh hash in phpMyAdmin or use hotfix.php after upload)
-- UPDATE users SET password_hash='$2y$10$...', is_active=1, nafath_verified=1;

UPDATE users SET sanad_limit=GREATEST(COALESCE(sanad_limit,0),500000), wallet_balance=GREATEST(COALESCE(wallet_balance,0),50000) WHERE role IN ('buyer','admin');

SELECT id, mobile, role, sanad_limit, wallet_balance FROM users ORDER BY id LIMIT 10;