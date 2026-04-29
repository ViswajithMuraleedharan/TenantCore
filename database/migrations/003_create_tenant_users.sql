CREATE TABLE tenant_users (
    id         CHAR(36) NOT NULL PRIMARY KEY,
    tenant_id  CHAR(36) NOT NULL,
    user_id    CHAR(36) NOT NULL,
    role       ENUM('owner','admin','member','viewer') NOT NULL DEFAULT 'member',
    invited_by CHAR(36) NULL,
    joined_at  TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE  KEY uq_tenant_user  (tenant_id, user_id),
    INDEX       idx_tenant_id   (tenant_id),
    INDEX       idx_user_id     (user_id),
    CONSTRAINT fk_tu_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_tu_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);