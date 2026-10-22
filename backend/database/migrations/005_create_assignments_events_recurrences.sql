CREATE TABLE IF NOT EXISTS task_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    workspace_id CHAR(26) NOT NULL,
    proposed_by_user_id BIGINT UNSIGNED NOT NULL,
    assigned_to_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    proposed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_at TIMESTAMP NULL,
    CONSTRAINT fk_assignments_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_proposer FOREIGN KEY (proposed_by_user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_assignee FOREIGN KEY (assigned_to_user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_assignments_assignee_status (assigned_to_user_id, status),
    INDEX idx_assignments_task_status (task_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_recurrences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    task_id BIGINT UNSIGNED NOT NULL,
    workspace_id CHAR(26) NOT NULL,
    rule VARCHAR(255) NOT NULL,
    timezone VARCHAR(64) NOT NULL DEFAULT 'Europe/Madrid',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recurrences_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_recurrences_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    UNIQUE KEY uq_recurrences_task (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_occurrences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    recurrence_id BIGINT UNSIGNED NOT NULL,
    task_id BIGINT UNSIGNED NOT NULL,
    workspace_id CHAR(26) NOT NULL,
    occurrence_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'skipped') NOT NULL DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    CONSTRAINT fk_occurrences_recurrence FOREIGN KEY (recurrence_id) REFERENCES task_recurrences (id) ON DELETE CASCADE,
    CONSTRAINT fk_occurrences_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_occurrences_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    UNIQUE KEY uq_occurrences_date (recurrence_id, occurrence_date),
    INDEX idx_occurrences_workspace_date (workspace_id, occurrence_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workspace_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    workspace_id CHAR(26) NOT NULL,
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    visibility ENUM('private', 'shared') NOT NULL DEFAULT 'shared',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_events_creator FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_events_workspace_start (workspace_id, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
