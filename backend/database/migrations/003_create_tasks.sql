CREATE TABLE IF NOT EXISTS tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workspace_id CHAR(26) NOT NULL,
    owner_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    visibility ENUM('private', 'shared') NOT NULL DEFAULT 'shared',
    status ENUM('pending', 'completed', 'archived') NOT NULL DEFAULT 'pending',
    due_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_tasks_owner FOREIGN KEY (owner_user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_tasks_workspace_status_due (workspace_id, status, due_at),
    INDEX idx_tasks_owner (owner_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
