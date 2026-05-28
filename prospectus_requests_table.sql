-- Add prospectus requests table to goodness_omogo_db
USE goodness_omogo_db;

CREATE TABLE IF NOT EXISTS prospectus_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    parent_name     VARCHAR(100) NOT NULL,
    email           VARCHAR(100) NOT NULL,
    phone           VARCHAR(20),
    student_name    VARCHAR(100) NOT NULL,
    grade_level     VARCHAR(20) NOT NULL,
    how_heard       TEXT,
    status          ENUM('Pending','Sent','Failed') DEFAULT 'Pending',
    admin_notes     TEXT,
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at         TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_email  (email)
);

-- Also add setting for school email if not exists
INSERT IGNORE INTO school_settings (setting_key, setting_label, setting_group, setting_value)
VALUES ('school_email', 'School Email', 'contact', 'golaedu2026@gmail.com');
