-- ============================================================
-- GOLA COMMUNICATION LOG TABLE
-- Run this on goodness_omogo_db
-- ============================================================
USE goodness_omogo_db;

CREATE TABLE IF NOT EXISTS communication_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    sent_by      INT NOT NULL,                          -- admin_users.id
    channel      ENUM('email','sms') NOT NULL,
    recipient_type ENUM('single','bulk','class') NOT NULL,
    class_id     INT NULL,                              -- if class bulk send
    student_ids  TEXT NULL,                             -- JSON array of student ids
    subject      VARCHAR(255),                          -- email only
    message      TEXT NOT NULL,
    recipient_count INT DEFAULT 0,
    failed_count    INT DEFAULT 0,
    status       ENUM('sent','partial','failed') DEFAULT 'sent',
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_sent_at (sent_at),
    INDEX idx_channel (channel)
);
