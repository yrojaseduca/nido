CREATE TABLE IF NOT EXISTS workspace_invitations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workspace_id CHAR(26) NOT NULL,
    invited_by_user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(254) NOT NULL,
    role ENUM('admin', 'member', 'guest') NOT NULL DEFAULT 'member',
    token_hash CHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'accepted', 'revoked', 'expired') NOT NULL DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invitations_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_invitations_inviter FOREIGN KEY (invited_by_user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_invitations_workspace_status (workspace_id, status),
    INDEX idx_invitations_email_status (email, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
