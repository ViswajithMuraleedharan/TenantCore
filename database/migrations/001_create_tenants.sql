CREATE TABLE tenants (
    id         CHAR(36)     NOT NULL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    plan       ENUM('free','starter','pro','enterprise') NOT NULL DEFAULT 'free',
    status     ENUM('active','suspended','cancelled')    NOT NULL DEFAULT 'active',
    settings   JSON         NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug   (slug),
    INDEX idx_status (status)
);