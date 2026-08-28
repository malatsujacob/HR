-- Migration: create offboard_audit
CREATE TABLE IF NOT EXISTS offboard_audit (
    audit_id SERIAL PRIMARY KEY,
    offboard_id INTEGER,
    original_employee_id INTEGER,
    snapshot JSONB,
    action VARCHAR(50),
    actor VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
