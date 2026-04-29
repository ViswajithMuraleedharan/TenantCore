CREATE TABLE subscriptions (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    tenant_id           CHAR(36)     NOT NULL UNIQUE,
    stripe_customer_id  VARCHAR(100) NULL,
    stripe_sub_id       VARCHAR(100) NULL,
    plan                VARCHAR(50)  NOT NULL DEFAULT 'free',
    status              VARCHAR(50)  NOT NULL DEFAULT 'trialing',
    trial_ends_at       TIMESTAMP    NULL,
    current_period_end  TIMESTAMP    NULL,
    cancel_at_period_end BOOLEAN     NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stripe_customer (stripe_customer_id),
    INDEX idx_stripe_sub      (stripe_sub_id),
    CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);