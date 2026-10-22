ALTER TABLE tasks
    ADD COLUMN category_id BIGINT UNSIGNED NULL,
    ADD COLUMN organizer_user_id BIGINT UNSIGNED NULL,
    ADD COLUMN estimated_minutes SMALLINT UNSIGNED NULL,
    ADD COLUMN actual_minutes SMALLINT UNSIGNED NULL,
    ADD COLUMN effort TINYINT UNSIGNED NULL,
    ADD COLUMN instructions TEXT NULL,
    ADD CONSTRAINT chk_tasks_effort CHECK (effort IS NULL OR effort BETWEEN 1 AND 5),
    ADD CONSTRAINT fk_tasks_organizer FOREIGN KEY (organizer_user_id) REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS workspace_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workspace_id CHAR(26) NOT NULL,
    name VARCHAR(120) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#6655ED',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    UNIQUE KEY uq_categories_workspace_name (workspace_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tasks
    ADD CONSTRAINT fk_tasks_category FOREIGN KEY (category_id) REFERENCES workspace_categories (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS task_checklist_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    workspace_id CHAR(26) NOT NULL,
    title VARCHAR(255) NOT NULL,
    completed TINYINT(1) NOT NULL DEFAULT 0,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_checklist_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_checklist_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    INDEX idx_checklist_task_position (task_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_comments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    workspace_id CHAR(26) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_comments_task_created (task_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
