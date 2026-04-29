CREATE TABLE audit_log (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id  CHAR(36)        NOT NULL,
    user_id    CHAR(36)        NULL,
    action     VARCHAR(100)    NOT NULL,
    entity     VARCHAR(100)    NULL,
    entity_id  CHAR(36)        NULL,
    meta       JSON            NULL,
    ip_address VARCHAR(45)     NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant   (tenant_id),
    INDEX idx_user     (user_id),
    INDEX idx_action   (action),
    INDEX idx_created  (created_at)
);