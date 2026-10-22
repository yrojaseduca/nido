ALTER TABLE users
    ADD COLUMN email_verification_token_hash CHAR(64) NULL,
    ADD COLUMN email_verification_expires_at TIMESTAMP NULL,
    ADD UNIQUE KEY uq_users_verification_token (email_verification_token_hash);
