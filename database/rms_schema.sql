

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone  = "+03:00";   -- 
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE DATABASE IF NOT EXISTS `rms_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `rms_db`;

-
  `id`                INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`         VARCHAR(120) NOT NULL,
  `email`             VARCHAR(180) NOT NULL,
  `phone`             VARCHAR(20)  DEFAULT NULL,
  `id_number`         VARCHAR(30)  DEFAULT NULL COMMENT 'National ID or Passport',
  `password_hash`     VARCHAR(255) DEFAULT NULL COMMENT 'NULL until user sets their password',
  `password_setup_token` CHAR(64)  DEFAULT NULL COMMENT 'SHA-256 hash for initial password setup',
  `password_setup_expires` DATETIME DEFAULT NULL COMMENT 'Expiry time for setup token',
  `role`              ENUM('admin','caretaker','tenant') NOT NULL,
  `status`            ENUM('active','inactive','suspended','prospective','moved_out','terminated','pending_setup','pending_approval') NOT NULL DEFAULT 'pending_setup',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = user must set a new password on next login',
  `avatar`            VARCHAR(255) DEFAULT NULL COMMENT 'Relative path to profile image',
  `emergency_contact` VARCHAR(200) DEFAULT NULL,
  `preferred_apartment_id` INT UNSIGNED DEFAULT NULL COMMENT 'FK → apartments (tenant preference)',
  `notes`                  TEXT         DEFAULT NULL COMMENT 'Registration notes',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role`   (`role`),
  KEY `idx_users_status` (`status`),
  KEY `idx_password_setup_token` (`password_setup_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
CREATE TABLE IF NOT EXISTS `apartments` (
  `id`           INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(120) NOT NULL,
  `location`     VARCHAR(255) NOT NULL,
  `county`       VARCHAR(80)  DEFAULT NULL,
  `town`         VARCHAR(80)  DEFAULT NULL,
  `estate`       VARCHAR(80)  DEFAULT NULL,
  `street`       VARCHAR(120) DEFAULT NULL,
  `floors`       TINYINT      UNSIGNED DEFAULT 1,
  `total_units`  SMALLINT     UNSIGNED DEFAULT 0,
  `caretaker_id` INT          UNSIGNED DEFAULT NULL COMMENT 'FK → users (caretaker)',
  `status`       ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `description`  TEXT DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_apt_caretaker` (`caretaker_id`),
  CONSTRAINT `fk_apt_caretaker`
    FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `units` (
  `id`            INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartment_id`  INT          UNSIGNED NOT NULL,
  `unit_number`   VARCHAR(20)  NOT NULL,
  `unit_type`     ENUM('Bedsitter','Studio','Self-Contained','1 Bedroom','2 Bedroom','3 Bedroom','4 Bedroom','Penthouse','Maisonette','Office','Shop','Other')
                               NOT NULL DEFAULT '1 Bedroom',
  `floor`         TINYINT      UNSIGNED DEFAULT 0,
  `bathrooms`     TINYINT      UNSIGNED DEFAULT 1,
  `monthly_rent`  DECIMAL(10,2) NOT NULL,
  `deposit`       DECIMAL(10,2) DEFAULT 0.00,
  `status`        ENUM('Vacant','Occupied','Under Maintenance') NOT NULL DEFAULT 'Vacant',
  `features`      JSON          DEFAULT NULL COMMENT 'Array of amenity strings',
  `notes`         TEXT          DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unit_apt_number` (`apartment_id`, `unit_number`),
  KEY `idx_unit_status` (`status`),
  CONSTRAINT `fk_unit_apartment`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `tenant_units` (
  `id`            INT           UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`     INT           UNSIGNED NOT NULL,
  `unit_id`       INT           UNSIGNED NOT NULL,
  `move_in_date`  DATE          DEFAULT NULL COMMENT 'Actual move-in date',
  `move_out_date` DATE          DEFAULT NULL COMMENT 'Actual move-out date',
  `lease_start`   DATE          DEFAULT NULL,
  `lease_end`     DATE          DEFAULT NULL,
  `monthly_rent`  DECIMAL(10,2) DEFAULT NULL COMMENT 'Rent at time of assignment',
  `status`        ENUM('active','ended','terminated') NOT NULL DEFAULT 'active',
  `notes`         TEXT          DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tu_tenant`  (`tenant_id`),
  KEY `idx_tu_unit`    (`unit_id`),
  KEY `idx_tu_status`  (`status`),
  CONSTRAINT `fk_tu_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tu_unit`
    FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `payments` (
  `id`               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`        INT          UNSIGNED NOT NULL,
  `unit_id`          INT          UNSIGNED DEFAULT NULL,
  `apartment_id`     INT          UNSIGNED DEFAULT NULL,
  `month`            ENUM('January','February','March','April','May','June',
                          'July','August','September','October','November','December')
                                  NOT NULL,
  `year`             YEAR         NOT NULL,
  `amount`           DECIMAL(10,2) NOT NULL,
  `payment_method`   ENUM('M-PESA','M-PESA Paybill','Airtel Money','PDQ/POS',
                          'Bank Transfer','Cash','Cheque','Other')
                                  NOT NULL DEFAULT 'M-PESA',
  `reference_number` VARCHAR(80)  DEFAULT NULL COMMENT 'M-PESA/Airtel transaction ID',
  `receipt_number`   VARCHAR(80)  DEFAULT NULL COMMENT 'System-generated receipt ref',
  `cheque_number`    VARCHAR(60)  DEFAULT NULL,
  `cheque_bank`      VARCHAR(80)  DEFAULT NULL,
  `cheque_status`    ENUM('Pending','Cleared','Bounced') DEFAULT NULL,
  `payment_date`     DATE         DEFAULT NULL,
  `status`           ENUM('Paid','Pending','Partial','Overdue','Waived')
                                  NOT NULL DEFAULT 'Pending',
  `payment_type`     ENUM('Rent','Deposit','Penalty','Other') NOT NULL DEFAULT 'Rent'
                                  COMMENT 'Category of payment',
  `expected_amount`  DECIMAL(10,2) DEFAULT NULL COMMENT 'Rent amount due for this period',
  `balance`          DECIMAL(10,2) DEFAULT NULL COMMENT 'Positive = still owed; negative = credit for next month',
  `carried_credit`   DECIMAL(10,2) DEFAULT NULL COMMENT 'Credit carried forward from the previous month',
  `notes`            TEXT         DEFAULT NULL,
  `recorded_by`      INT          UNSIGNED DEFAULT NULL COMMENT 'FK → users (admin/caretaker)',
  `confirmed_by`     INT          UNSIGNED DEFAULT NULL COMMENT 'FK → users (who confirmed payment)',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_tenant_month_year` (`tenant_id`, `month`, `year`),
  KEY `idx_pay_status`   (`status`),
  KEY `idx_pay_date`     (`payment_date`),
  KEY `idx_pay_unit`     (`unit_id`),
  KEY `idx_pay_apt`      (`apartment_id`),
  KEY `idx_pay_balance`  (`balance`),
  CONSTRAINT `fk_pay_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_unit`
    FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_apartment`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_recorded_by`
    FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_confirmed_by`
    FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id`             INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id`      INT          UNSIGNED NOT NULL,
  `unit_id`        INT          UNSIGNED DEFAULT NULL,
  `category`       ENUM('Plumbing','Electrical','Structural / Building','Appliances',
                        'Security / Lock','Pest Control','Cleaning / Sanitation','Other')
                               NOT NULL DEFAULT 'Other',
  `priority`       ENUM('Normal','Urgent','Emergency') NOT NULL DEFAULT 'Normal',
  `title`          VARCHAR(200) NOT NULL,
  `description`    TEXT         NOT NULL,
  `preferred_time` VARCHAR(60)  DEFAULT NULL,
  `photos`         JSON         DEFAULT NULL COMMENT 'Array of image paths',
  `status`         ENUM('Pending','In Progress','Resolved','Cancelled')
                               NOT NULL DEFAULT 'Pending',
  `caretaker_notes` TEXT        DEFAULT NULL,
  `resolved_at`    DATETIME     DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mr_tenant`   (`tenant_id`),
  KEY `idx_mr_unit`     (`unit_id`),
  KEY `idx_mr_status`   (`status`),
  KEY `idx_mr_priority` (`priority`),
  CONSTRAINT `fk_mr_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mr_unit`
    FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `maintenance_logs` (
  `id`         INT     UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT     UNSIGNED NOT NULL,
  `updated_by` INT     UNSIGNED DEFAULT NULL,
  `status`     VARCHAR(40)  DEFAULT NULL,
  `notes`      TEXT         DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ml_request` (`request_id`),
  CONSTRAINT `fk_ml_request`
    FOREIGN KEY (`request_id`) REFERENCES `maintenance_requests` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ml_updater`
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `shared_expenses` (
  `id`           INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `apartment_id` INT          UNSIGNED DEFAULT NULL,
  `unit_id`      INT          UNSIGNED DEFAULT NULL COMMENT 'NULL = apartment-wide charge',
  `tenant_id`    INT          UNSIGNED DEFAULT NULL,
  `expense_type` ENUM('Plumbing','Electrical','Structural','Appliances',
                      'Security','Cleaning','Pest Control','Locks & Keys',
                      'Painting','General Maintenance','Water','Electricity',
                      'Garbage','Internet','Other')
                              NOT NULL DEFAULT 'Other',
  `cost_category` ENUM('Labour','Materials','Equipment','Contractor','Permit','Other')
                              NOT NULL DEFAULT 'Other' COMMENT 'What the money was spent on',
  `vendor`       VARCHAR(120) DEFAULT NULL COMMENT 'Supplier, technician or contractor name',
  `paid_by`      INT          UNSIGNED DEFAULT NULL COMMENT 'FK to users (admin/caretaker who paid)',
  `receipt_ref`  VARCHAR(80)  DEFAULT NULL COMMENT 'Receipt or invoice number',
  `expense_date` DATE         DEFAULT NULL COMMENT 'Actual date money was spent',
  `description`  VARCHAR(255) DEFAULT NULL,
  `amount`       DECIMAL(10,2) NOT NULL,
  `month`        ENUM('January','February','March','April','May','June',
                      'July','August','September','October','November','December')
                              NOT NULL,
  `year`         YEAR         NOT NULL,
  `due_date`     DATE         DEFAULT NULL,
  `status`       ENUM('Paid','Pending','Cancelled') NOT NULL DEFAULT 'Paid',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exp_tenant`    (`tenant_id`),
  KEY `idx_exp_apartment` (`apartment_id`),
  KEY `idx_exp_status`    (`status`),
  KEY `idx_exp_paid_by`   (`paid_by`),
  CONSTRAINT `fk_exp_apartment`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_exp_unit`
    FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_exp_tenant`
    FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_exp_paid_by`
    FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `announcements` (
  `id`           INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by`   INT          UNSIGNED NOT NULL,
  `title`        VARCHAR(200) NOT NULL,
  `body`         TEXT         NOT NULL,
  `type`         ENUM('General','Urgent','Information') NOT NULL DEFAULT 'General',
  `target`       ENUM('all_tenants','all_caretakers','specific_apartment','specific_tenant')
                              NOT NULL DEFAULT 'all_tenants',
  `target_id`    INT          UNSIGNED DEFAULT NULL COMMENT 'apartment_id or tenant_id when target is specific',
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ann_creator` (`created_by`),
  KEY `idx_ann_type`    (`type`),
  CONSTRAINT `fk_ann_creator`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `messages` (
  `id`           INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_user_id` INT          UNSIGNED NOT NULL,
  `to_user_id`   INT          UNSIGNED NOT NULL,
  `subject`      VARCHAR(200) NOT NULL,
  `body`         TEXT         NOT NULL,
  `is_read`      TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`      DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_msg_from` (`from_user_id`),
  KEY `idx_msg_to`   (`to_user_id`),
  KEY `idx_msg_read` (`is_read`),
  CONSTRAINT `fk_msg_from`
    FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_msg_to`
    FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT      UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT      UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL COMMENT 'SHA-256 hash of the raw token',
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_user`  (`user_id`),
  KEY `idx_pr_token` (`token_hash`),
  CONSTRAINT `fk_pr_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id`         INT      UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT      UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rt_user` (`user_id`),
  CONSTRAINT `fk_rt_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `system_settings` (
  `id`         INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(80)  NOT NULL,
  `value`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `caretaker_apartments` (
  `caretaker_id`  INT UNSIGNED NOT NULL,
  `apartment_id`  INT UNSIGNED NOT NULL,
  `assigned_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`caretaker_id`, `apartment_id`),
  CONSTRAINT `fk_ca_caretaker`
    FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ca_apartment`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `caretaker_assignments` (
  `id`            INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `caretaker_id`  INT          UNSIGNED NOT NULL,
  `apartment_id`  INT          UNSIGNED NOT NULL,
  `status`        ENUM('active','removed') NOT NULL DEFAULT 'active',
  `assigned_date` DATE         DEFAULT NULL COMMENT 'Date caretaker was assigned',
  `removed_date`  DATE         DEFAULT NULL COMMENT 'Date caretaker was removed',
  `notes`         TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_casn_caretaker` (`caretaker_id`),
  KEY `idx_casn_apartment` (`apartment_id`),
  KEY `idx_casn_status`    (`status`),
  CONSTRAINT `fk_casn_caretaker`
    FOREIGN KEY (`caretaker_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_casn_apartment`
    FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_preferred_apartment`
    FOREIGN KEY (`preferred_apartment_id`) REFERENCES `apartments` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;


CREATE TABLE IF NOT EXISTS `caretaker_history` (
  `id`               INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_user_id` INT          UNSIGNED DEFAULT NULL COMMENT 'Original user.id before deletion',
  `full_name`        VARCHAR(120) NOT NULL,
  `email`            VARCHAR(180) NOT NULL,
  `phone`            VARCHAR(20)  DEFAULT NULL,
  `id_number`        VARCHAR(30)  DEFAULT NULL,
  `apartment_id`     INT          UNSIGNED DEFAULT NULL COMMENT 'Last assigned apartment',
  `apartment_name`   VARCHAR(120) DEFAULT NULL,
  `assigned_date`    DATE         DEFAULT NULL COMMENT 'Date assigned to apartment',
  `removed_date`     DATE         DEFAULT NULL COMMENT 'Date removed from position',
  `removal_reason`   TEXT         DEFAULT NULL,
  `removed_by_name`  VARCHAR(120) DEFAULT NULL COMMENT 'Admin who removed them',
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ch_original_user` (`original_user_id`),
  KEY `idx_ch_apartment` (`apartment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`        INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`   INT          UNSIGNED DEFAULT NULL,
  `action`    VARCHAR(80)  NOT NULL,
  `target`    VARCHAR(80)  DEFAULT NULL,
  `target_id` INT          UNSIGNED DEFAULT NULL,
  `details`   TEXT         DEFAULT NULL,
  `ip`        VARCHAR(45)  DEFAULT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_user`   (`user_id`),
  KEY `idx_al_action` (`action`),
  CONSTRAINT `fk_al_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT          UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT          UNSIGNED NOT NULL,
  `type`       VARCHAR(50)  NOT NULL DEFAULT 'general',
  `title`      VARCHAR(200) NOT NULL,
  `body`       TEXT         DEFAULT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`    DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`    (`user_id`),
  KEY `idx_notif_is_read` (`is_read`),
  CONSTRAINT `fk_notif_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




INSERT INTO `system_settings` (`key`, `value`) VALUES
  ('system_name',     'Rental Management System'),
  ('currency',        'KES'),
  ('date_format',     'D M Y'),
  ('rent_due_day',    '5'),
  ('penalty_percent', '10'),
  ('timezone',        'Africa/Nairobi'),
  ('version',         '2.0.0');


CREATE OR REPLACE VIEW `v_active_tenants` AS
SELECT
  u.id            AS tenant_id,
  u.full_name     AS tenant_name,
  u.email,
  u.phone,
  u.status        AS account_status,
  un.unit_number,
  un.unit_type,
  un.monthly_rent,
  a.name          AS apartment_name,
  a.location,
  tu.lease_start,
  tu.lease_end
FROM users u
JOIN tenant_units tu ON tu.tenant_id = u.id AND tu.status = 'active'
JOIN units       un  ON un.id = tu.unit_id
JOIN apartments   a  ON a.id  = un.apartment_id
WHERE u.role = 'tenant';


CREATE OR REPLACE VIEW `v_payment_summary` AS
SELECT
  u.id            AS tenant_id,
  u.full_name     AS tenant_name,
  un.unit_number,
  a.name          AS apartment_name,
  p.month,
  p.year,
  p.amount,
  p.payment_method,
  p.reference_number,
  p.payment_date,
  p.status
FROM payments p
JOIN users       u  ON u.id  = p.tenant_id
JOIN units       un ON un.id = p.unit_id
JOIN apartments   a ON a.id  = p.apartment_id
ORDER BY p.year DESC, FIELD(p.month,
  'January','February','March','April','May','June',
  'July','August','September','October','November','December') DESC;


CREATE OR REPLACE VIEW `v_apartment_occupancy` AS
SELECT
  a.id,
  a.name,
  a.location,
  a.total_units,
  COUNT(CASE WHEN un.status = 'Occupied' THEN 1 END) AS occupied,
  COUNT(CASE WHEN un.status = 'Vacant'   THEN 1 END) AS vacant,
  ROUND(COUNT(CASE WHEN un.status = 'Occupied' THEN 1 END) * 100.0 / NULLIF(a.total_units, 0), 1)
    AS occupancy_pct,
  u.full_name AS caretaker_name
FROM apartments a
LEFT JOIN units un ON un.apartment_id = a.id
LEFT JOIN users  u ON u.id = a.caretaker_id
GROUP BY a.id, a.name, a.location, a.total_units, u.full_name;
