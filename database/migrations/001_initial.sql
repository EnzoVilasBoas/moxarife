-- Moxarife: schema inicial do banco novo.
-- Execute em um banco vazio ou adapte o processo de migrations da aplicação.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY refresh_tokens_hash_unique (token_hash),
    KEY refresh_tokens_user_idx (user_id),
    CONSTRAINT refresh_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(180) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    description TEXT NULL,
    minimum_quantity DECIMAL(14, 3) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY inventory_items_code_unique (code),
    KEY inventory_items_name_idx (name),
    CONSTRAINT inventory_items_creator_fk FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('entry', 'exit', 'adjustment') NOT NULL,
    quantity DECIMAL(14, 3) NOT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY inventory_movements_item_date_idx (item_id, created_at),
    KEY inventory_movements_user_date_idx (user_id, created_at),
    CONSTRAINT inventory_movements_item_fk FOREIGN KEY (item_id) REFERENCES inventory_items (id) ON DELETE CASCADE,
    CONSTRAINT inventory_movements_user_fk FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT inventory_movements_quantity_check CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW inventory_stock AS
SELECT
    i.id AS item_id,
    i.code,
    i.name,
    i.unit,
    i.minimum_quantity,
    COALESCE(SUM(
        CASE
            WHEN m.type = 'entry' THEN m.quantity
            WHEN m.type = 'exit' THEN -m.quantity
            ELSE 0
        END
    ), 0) AS quantity
FROM inventory_items i
LEFT JOIN inventory_movements m ON m.item_id = i.id
WHERE i.active = 1
GROUP BY i.id, i.code, i.name, i.unit, i.minimum_quantity;
