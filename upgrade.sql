-- Run ONCE in phpMyAdmin against the EXISTING MyNotes database. Does not remove notes.
ALTER TABLE mn_users ADD COLUMN password_hash VARCHAR(255) NULL AFTER enabled;
CREATE TABLE IF NOT EXISTS mn_trusted_browsers (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,selector CHAR(32) NOT NULL UNIQUE,token_hash CHAR(64) NOT NULL,expires_at DATETIME NOT NULL,FOREIGN KEY(user_id) REFERENCES mn_users(id) ON DELETE CASCADE) ENGINE=InnoDB;
INSERT IGNORE INTO mn_settings(name,value) VALUES ('require_2fa','1');
