-- ============================================================
-- GOLA ADMISSIONS SYSTEM UPGRADE
-- Run on existing goodness_omogo_db
-- ============================================================

USE goodness_omogo_db;

-- ============================================================
-- 1. SCHOOL SETTINGS TABLE
--    Stores payment details, prospectus file, contact info
--    All editable from admin without touching code.
-- ============================================================
CREATE TABLE IF NOT EXISTS school_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_label VARCHAR(200),
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO school_settings (setting_key, setting_label, setting_group, setting_value) VALUES
-- Payment
('application_fee_amount',    'Application Fee Amount (₦)',        'payment', '5000'),
('application_fee_bank',      'Bank Name',                          'payment', 'First Bank Nigeria'),
('application_fee_account_no','Account Number',                     'payment', '1234567890'),
('application_fee_account_name','Account Name',                     'payment', 'Goodness Omogo Leadership Academy'),
('acceptance_fee_amount',     'Acceptance Fee Amount (₦)',          'payment', '25000'),
-- Prospectus
('prospectus_file',           'Prospectus PDF File',                'prospectus', ''),
('prospectus_updated_at',     'Prospectus Last Updated',            'prospectus', ''),
-- Admissions
('admissions_open',           'Admissions Open (1=yes, 0=no)',      'admissions', '1'),
('admissions_session',        'Current Admissions Session',         'admissions', '2025/2026'),
('entrance_exam_date',        'Next Entrance Exam Date',            'admissions', ''),
('entrance_exam_venue',       'Entrance Exam Venue',                'admissions', 'GOLA Main Campus, Ntezi, Ebonyi State'),
-- Contact
('school_email',              'School Email',                       'contact', 'golaedu2026@gmail.com'),
('school_phone',              'School Phone',                       'contact', '09125128213'),
('school_address',            'School Address',                     'contact', 'Ntezi, Ishielu LGA, Ebonyi State, Nigeria');

-- ============================================================
-- 2. ACADEMIC CALENDAR TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS academic_calendar (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    session_id  INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    event_date  DATE NOT NULL,
    end_date    DATE NULL,
    category    ENUM('Term Start','Term End','Holiday','Exam','Event','Deadline','Other') DEFAULT 'Event',
    description TEXT,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    INDEX idx_date (event_date),
    INDEX idx_session (session_id)
);

-- ============================================================
-- 3. ADMISSIONS APPLICATIONS TABLE
--    Mirrors the paper form exactly.
-- ============================================================
CREATE TABLE IF NOT EXISTS admissions_applications (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    application_no      VARCHAR(20) UNIQUE NOT NULL,   -- GOLA-APP-2025-0001

    -- Section A: Personal Info
    full_name           VARCHAR(150) NOT NULL,
    gender              ENUM('Male','Female') NOT NULL,
    date_of_birth       DATE NOT NULL,
    age                 INT,
    state_of_origin     VARCHAR(50),
    lga                 VARCHAR(50),
    nationality         VARCHAR(50) DEFAULT 'Nigerian',
    religion            VARCHAR(50),
    home_address        TEXT,
    phone_number        VARCHAR(20),

    -- Section B: Parent/Guardian
    father_name         VARCHAR(100),
    father_phone        VARCHAR(20),
    mother_name         VARCHAR(100),
    mother_phone        VARCHAR(20),
    guardian_name       VARCHAR(100),
    guardian_relationship VARCHAR(50),
    guardian_phone      VARCHAR(20),
    parent_email        VARCHAR(100),

    -- Section C: Academic Background
    last_school         VARCHAR(150),
    class_completed     VARCHAR(50),
    year_completed      YEAR,
    reason_for_leaving  TEXT,
    -- Documents
    has_last_result     TINYINT(1) DEFAULT 0,
    has_birth_cert      TINYINT(1) DEFAULT 0,
    has_passport_photos TINYINT(1) DEFAULT 0,

    -- Section D: Boarding & Health
    emergency_contact_name  VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    has_medical_condition   TINYINT(1) DEFAULT 0,
    medical_condition_details TEXT,
    has_allergies           TINYINT(1) DEFAULT 0,
    allergy_details         TEXT,
    special_diet            TINYINT(1) DEFAULT 0,
    special_diet_details    TEXT,
    special_care_needs      TEXT,
    family_doctor           VARCHAR(100),
    family_doctor_phone     VARCHAR(20),

    -- Section E: Sports
    sports_football     TINYINT(1) DEFAULT 0,
    sports_volleyball   TINYINT(1) DEFAULT 0,
    sports_basketball   TINYINT(1) DEFAULT 0,
    sports_badminton    TINYINT(1) DEFAULT 0,
    sports_long_jump    TINYINT(1) DEFAULT 0,
    sports_triple_jump  TINYINT(1) DEFAULT 0,
    sports_shot_put     TINYINT(1) DEFAULT 0,
    sports_discus       TINYINT(1) DEFAULT 0,
    sports_javelin      TINYINT(1) DEFAULT 0,
    sports_table_tennis TINYINT(1) DEFAULT 0,
    sports_handball     TINYINT(1) DEFAULT 0,
    sports_participated_before TINYINT(1) DEFAULT 0,
    sports_level        ENUM('','School Level','Inter-School','State Level','National Level') DEFAULT '',
    sports_awards       TEXT,

    -- Section F: Leadership & Entrepreneurship
    interested_in_leadership TINYINT(1) DEFAULT 0,
    held_leadership_before   TINYINT(1) DEFAULT 0,
    leadership_details       TEXT,
    interest_public_speaking TINYINT(1) DEFAULT 0,
    interest_business        TINYINT(1) DEFAULT 0,
    interest_agriculture     TINYINT(1) DEFAULT 0,
    interest_ict             TINYINT(1) DEFAULT 0,
    interest_tailoring       TINYINT(1) DEFAULT 0,
    interest_arts_music      TINYINT(1) DEFAULT 0,
    interest_debate          TINYINT(1) DEFAULT 0,
    interest_other           VARCHAR(100),
    future_career            TEXT,

    -- Section G: Character
    ever_suspended      TINYINT(1) DEFAULT 0,
    suspension_details  TEXT,
    special_talents     TEXT,

    -- Application meta
    grade_applying_for  VARCHAR(20) NOT NULL,
    session_applying    VARCHAR(20) NOT NULL,
    how_heard           TEXT,

    -- Payment proof
    payment_amount      DECIMAL(10,2),
    payment_bank        VARCHAR(100),
    payment_date        DATE,
    payment_proof_file  VARCHAR(255),   -- uploaded filename
    payment_verified    TINYINT(1) DEFAULT 0,

    -- Status
    status              ENUM('Pending','Under Review','Shortlisted','Admitted','Rejected','Enrolled') DEFAULT 'Pending',
    admin_notes         TEXT,
    exam_date           DATE NULL,
    exam_no             VARCHAR(20),
    reviewed_by         INT NULL,
    reviewed_at         TIMESTAMP NULL,
    admitted_at         TIMESTAMP NULL,
    enrolled_at         TIMESTAMP NULL,
    enrolled_as_student_id INT NULL,  -- links to students.id after enrolment

    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (enrolled_as_student_id) REFERENCES students(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_application_no (application_no),
    INDEX idx_session (session_applying)
);

-- ============================================================
-- 4. Create uploads directory hint (reminder)
-- ============================================================
-- Make sure this folder exists and is writable on your server:
--   /var/www/html/gola/uploads/applications/
--   /var/www/html/gola/uploads/prospectus/
-- Run: mkdir -p /var/www/html/gola/uploads/applications
--      mkdir -p /var/www/html/gola/uploads/prospectus
--      chown -R www-data:www-data /var/www/html/gola/uploads
