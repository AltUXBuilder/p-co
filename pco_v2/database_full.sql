-- ============================================================
--  Prescribe & Co. — Complete Database Schema
--  Version: 1.0.0  |  MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `prescribeco_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `prescribeco_db`;

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
CREATE TABLE `users` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`          VARCHAR(191)  NOT NULL UNIQUE,
  `password_hash`  VARCHAR(255)  NOT NULL,
  `role`           ENUM('admin','prescriber','dispenser','patient') NOT NULL DEFAULT 'patient',
  `first_name`     VARCHAR(100)  NOT NULL,
  `last_name`      VARCHAR(100)  NOT NULL,
  `phone`          VARCHAR(30)   DEFAULT NULL,
  `date_of_birth`  DATE          DEFAULT NULL,
  `gender`         ENUM('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `nhs_number`     VARCHAR(20)   DEFAULT NULL,
  `is_active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `email_verified` TINYINT(1)    NOT NULL DEFAULT 0,
  `verify_token`   VARCHAR(64)   DEFAULT NULL,
  `reset_token`    VARCHAR(64)   DEFAULT NULL,
  `reset_expires`  DATETIME      DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_role`  (`role`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- PATIENT ADDRESSES
-- -------------------------------------------------------
CREATE TABLE `patient_addresses` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `label`      VARCHAR(50)  NOT NULL DEFAULT 'Home',
  `line1`      VARCHAR(200) NOT NULL,
  `line2`      VARCHAR(200) DEFAULT NULL,
  `city`       VARCHAR(100) NOT NULL,
  `county`     VARCHAR(100) DEFAULT NULL,
  `postcode`   VARCHAR(10)  NOT NULL,
  `country`    VARCHAR(60)  NOT NULL DEFAULT 'United Kingdom',
  `is_default` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- CONDITIONS
-- -------------------------------------------------------
CREATE TABLE `conditions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug`        VARCHAR(100) NOT NULL UNIQUE,
  `name`        VARCHAR(200) NOT NULL,
  `gender`      ENUM('male','female','all') NOT NULL DEFAULT 'all',
  `description` TEXT         DEFAULT NULL,
  `icon`        VARCHAR(100) DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- PRODUCTS
-- -------------------------------------------------------
CREATE TABLE `products` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `condition_id`          INT UNSIGNED NOT NULL,
  `sku`                   VARCHAR(60)  NOT NULL UNIQUE,
  `name`                  VARCHAR(200) NOT NULL,
  `brand`                 VARCHAR(100) DEFAULT NULL,
  `description`           TEXT         DEFAULT NULL,
  `dosage_form`           VARCHAR(100) DEFAULT NULL,
  `strength`              VARCHAR(100) DEFAULT NULL,
  `price`                 DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `price_currency`        VARCHAR(3)   NOT NULL DEFAULT 'GBP',
  `requires_prescription` TINYINT(1)   NOT NULL DEFAULT 1,
  `stock_qty`             INT          NOT NULL DEFAULT 0,
  `image_url`             VARCHAR(255) DEFAULT NULL,
  `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`            INT          NOT NULL DEFAULT 0,
  `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`condition_id`) REFERENCES `conditions`(`id`) ON DELETE RESTRICT,
  INDEX `idx_condition` (`condition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- QUESTIONNAIRE TEMPLATES
-- -------------------------------------------------------
CREATE TABLE `questionnaire_templates` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `condition_id` INT UNSIGNED NOT NULL,
  `version`      INT          NOT NULL DEFAULT 1,
  `title`        VARCHAR(200) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`condition_id`) REFERENCES `conditions`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_condition_version` (`condition_id`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- QUESTIONNAIRE QUESTIONS
-- -------------------------------------------------------
CREATE TABLE `questionnaire_questions` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_id`     INT UNSIGNED NOT NULL,
  `question_key`    VARCHAR(100) NOT NULL,
  `question_text`   TEXT         NOT NULL,
  `question_type`   ENUM('text','textarea','radio','checkbox','select','number','date','boolean') NOT NULL DEFAULT 'text',
  `options_json`    JSON         DEFAULT NULL,
  `is_required`     TINYINT(1)   NOT NULL DEFAULT 1,
  `validation_rule` VARCHAR(255) DEFAULT NULL,
  `disqualify_if`   VARCHAR(500) DEFAULT NULL COMMENT 'JSON rule that auto-rejects consultation',
  `step_number`     INT          NOT NULL DEFAULT 1,
  `sort_order`      INT          NOT NULL DEFAULT 0,
  `help_text`       TEXT         DEFAULT NULL,
  FOREIGN KEY (`template_id`) REFERENCES `questionnaire_templates`(`id`) ON DELETE CASCADE,
  INDEX `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- CONSULTATIONS
-- -------------------------------------------------------
CREATE TABLE `consultations` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `patient_id`       INT UNSIGNED NOT NULL,
  `condition_id`     INT UNSIGNED NOT NULL,
  `product_id`       INT UNSIGNED DEFAULT NULL,
  `template_id`      INT UNSIGNED NOT NULL,
  `status`           ENUM('draft','submitted','under_review','approved','rejected','cancelled') NOT NULL DEFAULT 'draft',
  `submitted_at`     DATETIME     DEFAULT NULL,
  `reviewed_by`      INT UNSIGNED DEFAULT NULL,
  `reviewed_at`      DATETIME     DEFAULT NULL,
  `review_notes`     TEXT         DEFAULT NULL,
  `rejection_reason` TEXT         DEFAULT NULL,
  `ip_address`       VARCHAR(45)  DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`)   REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`condition_id`) REFERENCES `conditions`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`product_id`)   REFERENCES `products`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`template_id`)  REFERENCES `questionnaire_templates`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`reviewed_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_patient` (`patient_id`),
  INDEX `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- CONSULTATION ANSWERS
-- -------------------------------------------------------
CREATE TABLE `consultation_answers` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `consultation_id`  INT UNSIGNED NOT NULL,
  `question_id`      INT UNSIGNED NOT NULL,
  `question_key`     VARCHAR(100) NOT NULL,
  `answer_value`     TEXT         DEFAULT NULL,
  `answered_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`)     REFERENCES `questionnaire_questions`(`id`) ON DELETE RESTRICT,
  UNIQUE KEY `uq_consult_question` (`consultation_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- PRESCRIPTIONS
-- -------------------------------------------------------
CREATE TABLE `prescriptions` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `consultation_id`  INT UNSIGNED NOT NULL,
  `patient_id`       INT UNSIGNED NOT NULL,
  `prescriber_id`    INT UNSIGNED NOT NULL,
  `prescription_ref` VARCHAR(20)  NOT NULL UNIQUE,
  `status`           ENUM('active','dispensed','cancelled','expired') NOT NULL DEFAULT 'active',
  `issue_date`       DATE         NOT NULL,
  `expiry_date`      DATE         NOT NULL,
  `clinical_notes`   TEXT         DEFAULT NULL,
  `is_repeat`        TINYINT(1)   NOT NULL DEFAULT 0,
  `repeat_count`     INT          NOT NULL DEFAULT 0,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`consultation_id`) REFERENCES `consultations`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`patient_id`)      REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`prescriber_id`)   REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_patient`    (`patient_id`),
  INDEX `idx_prescriber` (`prescriber_id`),
  INDEX `idx_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- PRESCRIPTION ITEMS  (multiple medications per Rx)
-- -------------------------------------------------------
CREATE TABLE `prescription_items` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `prescription_id`     INT UNSIGNED NOT NULL,
  `product_id`          INT UNSIGNED NOT NULL,
  `medication_name`     VARCHAR(255) NOT NULL,
  `strength`            VARCHAR(100) DEFAULT NULL,
  `dosage_form`         VARCHAR(100) DEFAULT NULL,
  `dosage_instructions` TEXT         NOT NULL,
  `quantity`            INT          NOT NULL DEFAULT 1,
  `quantity_unit`       VARCHAR(50)  NOT NULL DEFAULT 'tablet(s)',
  `duration_days`       INT          DEFAULT NULL,
  `warnings`            TEXT         DEFAULT NULL,
  `dispensed_at`        DATETIME     DEFAULT NULL,
  `dispensed_by`        INT UNSIGNED DEFAULT NULL,
  `dispensed_qty`       INT          DEFAULT NULL,
  `status`              ENUM('pending','dispensed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`)      REFERENCES `products`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`dispensed_by`)    REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_prescription` (`prescription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- ORDERS
-- -------------------------------------------------------
CREATE TABLE `orders` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_ref`       VARCHAR(20)  NOT NULL UNIQUE,
  `patient_id`      INT UNSIGNED NOT NULL,
  `prescription_id` INT UNSIGNED DEFAULT NULL,
  `address_id`      INT UNSIGNED NOT NULL,
  `status`          ENUM('pending','processing','dispatched','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `subtotal`        DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `shipping_cost`   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `currency`        VARCHAR(3)   NOT NULL DEFAULT 'GBP',
  `payment_status`  ENUM('unpaid','paid','refunded','failed') NOT NULL DEFAULT 'unpaid',
  `payment_method`  VARCHAR(50)  DEFAULT NULL,
  `payment_ref`     VARCHAR(100) DEFAULT NULL,
  `notes`           TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`)      REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`address_id`)      REFERENCES `patient_addresses`(`id`) ON DELETE RESTRICT,
  INDEX `idx_patient` (`patient_id`),
  INDEX `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- ORDER ITEMS
-- -------------------------------------------------------
CREATE TABLE `order_items` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`             INT UNSIGNED NOT NULL,
  `prescription_item_id` INT UNSIGNED DEFAULT NULL,
  `product_id`           INT UNSIGNED NOT NULL,
  `product_name`         VARCHAR(255) NOT NULL,
  `quantity`             INT          NOT NULL DEFAULT 1,
  `unit_price`           DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `line_total`           DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`order_id`)             REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`product_id`)           REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- DELIVERIES
-- -------------------------------------------------------
CREATE TABLE `deliveries` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`        INT UNSIGNED NOT NULL UNIQUE,
  `address_id`      INT UNSIGNED NOT NULL,
  `requested_date`  DATE         NOT NULL,
  `delivery_window` ENUM('AM','PM','Evening','Any') NOT NULL DEFAULT 'Any',
  `tracking_number` VARCHAR(100) DEFAULT NULL,
  `carrier`         VARCHAR(100) DEFAULT NULL,
  `status`          ENUM('scheduled','collected','in_transit','out_for_delivery','delivered','failed','returned') NOT NULL DEFAULT 'scheduled',
  `dispatched_at`   DATETIME     DEFAULT NULL,
  `delivered_at`    DATETIME     DEFAULT NULL,
  `delivery_notes`  TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`address_id`) REFERENCES `patient_addresses`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- DISPENSING LABELS  (print audit trail)
-- -------------------------------------------------------
CREATE TABLE `dispensing_labels` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `prescription_item_id` INT UNSIGNED NOT NULL,
  `prescription_id`      INT UNSIGNED NOT NULL,
  `patient_id`           INT UNSIGNED NOT NULL,
  `generated_by`         INT UNSIGNED NOT NULL,
  `printed_at`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `label_data_json`      JSON         NOT NULL,
  FOREIGN KEY (`prescription_item_id`) REFERENCES `prescription_items`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`prescription_id`)      REFERENCES `prescriptions`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`patient_id`)           REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`generated_by`)         REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- AUDIT LOGS
-- -------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED  DEFAULT NULL,
  `action`       VARCHAR(100)  NOT NULL,
  `entity_type`  VARCHAR(60)   NOT NULL,
  `entity_id`    INT UNSIGNED  DEFAULT NULL,
  `details_json` JSON          DEFAULT NULL,
  `ip_address`   VARCHAR(45)   DEFAULT NULL,
  `user_agent`   VARCHAR(500)  DEFAULT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user`    (`user_id`),
  INDEX `idx_action`  (`action`),
  INDEX `idx_entity`  (`entity_type`, `entity_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- SYSTEM SETTINGS
-- -------------------------------------------------------
CREATE TABLE `system_settings` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT         DEFAULT NULL,
  `description`   VARCHAR(255) DEFAULT NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('pharmacy_name',            'Prescribe & Co.',                    'Pharmacy display name'),
('pharmacy_name_short',      'P&Co.',                              'Short brand name'),
('pharmacy_address',         '1 Harley Street, London, W1G 9QD',  'Registered pharmacy address'),
('pharmacy_phone',           '0800 000 0000',                      'Contact phone number'),
('pharmacy_email',           'hello@prescribeandco.co.uk',         'Contact email'),
('gphc_number',              '1234567',                            'GPhC registration number'),
('prescription_expiry_days', '28',                                 'Days until prescription expires'),
('label_footer_text',        'Keep out of reach of children. Store below 25°C.', 'Label footer'),
('stripe_public_key',        '',                                   'Stripe publishable key'),
('stripe_secret_key',        '',                                   'Stripe secret key'),
('shipping_cost',            '3.99',                               'Standard shipping cost GBP'),
('free_shipping_threshold',  '50.00',                              'Free shipping above this amount');

-- Conditions (prescription pathways for men + women, with shared weight loss journey)
INSERT INTO `conditions` (`slug`, `name`, `gender`, `description`, `icon`, `is_active`, `sort_order`) VALUES
('weight-loss',          'Weight Loss',           'all',    'Clinically-backed weight management programmes for men and women.', 'weight-scale', 1, 1),
('erectile-dysfunction', 'Erectile Dysfunction',  'male',   'Discreet, effective prescription treatments for ED.',           'heart-pulse',  1, 2),
('hair-loss-men',        'Hair Loss',             'male',   'Proven prescription treatments for male pattern baldness.',     'cut',          1, 3),
('digestive-health',     'Digestive Health',      'female', 'Relief from IBS, bloating and gut health concerns.',           'leaf',         1, 4),
('hair-loss-women',      'Hair Loss',             'female', 'Effective solutions for female hair thinning.',                 'cut',          1, 5),
('skin-health',          'Skin Health',           'female', 'Prescription skincare for acne, rosacea and more.',            'sparkles',     1, 6);

-- Products
INSERT INTO `products` (`condition_id`,`sku`,`name`,`brand`,`description`,`dosage_form`,`strength`,`price`,`requires_prescription`,`stock_qty`,`sort_order`) VALUES
-- Weight loss (1) shared for men + women
(1,'WLM-ORL-120','Orlistat','Generic','Prevents dietary fat absorption to support weight loss.','Capsule','120mg',49.99,1,100,1),
(1,'WLM-SEM-1',  'Semaglutide','Wegovy','Once-weekly GLP-1 injection for chronic weight management.','Injection','1mg/week',199.99,1,50,2),
-- ED (2)
(2,'ED-SIL-50',  'Sildenafil','Generic','PDE5 inhibitor — take 30–60 min before activity.','Tablet','50mg',24.99,1,200,1),
(2,'ED-SIL-100', 'Sildenafil','Generic','Higher strength sildenafil for ED.','Tablet','100mg',29.99,1,200,2),
(2,'ED-TAD-10',  'Tadalafil','Generic','Long-acting ED treatment — effective up to 36 hours.','Tablet','10mg',34.99,1,150,3),
(2,'ED-TAD-20',  'Tadalafil','Generic','Higher strength tadalafil.','Tablet','20mg',39.99,1,150,4),
-- Hair loss men (3) — stub
(3,'HLM-FIN-1',  'Finasteride','Generic','DHT blocker for male pattern baldness.','Tablet','1mg',19.99,1,300,1),
(3,'HLM-MIN-5',  'Minoxidil','Regaine','Topical solution to stimulate hair regrowth.','Solution','5%',22.99,0,250,2),
-- Digestive (4) — stub
(4,'DH-MEB-135', 'Mebeverine','Colofac','Antispasmodic for IBS and bowel cramping.','Tablet','135mg',14.99,1,400,1),
-- Hair loss women (5) — stub
(5,'HLW-MIN-2',  'Minoxidil','Regaine','Topical solution for women.','Solution','2%',22.99,0,200,1),
-- Skin health (6) — stub
(6,'SK-TRE-0025','Tretinoin','Generic','Retinoid cream for acne and skin texture.','Cream','0.025%',29.99,1,200,1);

-- ============================================================
-- QUESTIONNAIRE TEMPLATES — Full: Weight Loss + ED
-- Stubs: everything else
-- ============================================================

-- Template 1: Weight Loss (FULL)
INSERT INTO `questionnaire_templates` (`condition_id`,`version`,`title`,`description`) VALUES
(1, 1, 'Weight Loss Assessment', 'A short confidential questionnaire to help our prescribers recommend the safest, most effective weight loss treatment for you.');

INSERT INTO `questionnaire_questions`
  (`template_id`,`question_key`,`question_text`,`question_type`,`options_json`,`is_required`,`step_number`,`sort_order`,`help_text`,`disqualify_if`) VALUES
(1,'current_weight_kg',  'What is your current weight? (kg)',   'number',  NULL, 1, 1, 1, 'Enter to the nearest kilogram.',NULL),
(1,'height_cm',          'What is your height? (cm)',           'number',  NULL, 1, 1, 2, 'Enter to the nearest centimetre.',NULL),
(1,'weight_goal_kg',     'How much weight are you looking to lose? (kg)', 'number', NULL, 1, 1, 3, NULL, NULL),
(1,'previous_attempts',  'Have you tried to lose weight before?','radio',  '["Yes — multiple times","Yes — once","No, this is my first attempt"]', 1, 1, 4, NULL, NULL),
(1,'diet_description',   'How would you describe your current diet?','radio','["Balanced and varied","High in processed / fast food","Low carb or ketogenic","Vegetarian or vegan","Other"]', 1, 2, 5, NULL, NULL),
(1,'exercise_level',     'How physically active are you?',      'radio',   '["Sedentary (little to no exercise)","Lightly active (1–2 days per week)","Moderately active (3–4 days per week)","Very active (5+ days per week)"]', 1, 2, 6, NULL, NULL),
(1,'medical_conditions', 'Do you have any of the following conditions?', 'checkbox', '["Type 2 diabetes","High blood pressure","Heart disease","Thyroid disorder","Kidney disease","None of the above"]', 1, 3, 7, 'Select all that apply.', NULL),
(1,'current_medications','Are you currently taking any medications?', 'textarea', NULL, 1, 3, 8, 'Include prescription drugs, OTC medicines and supplements.', NULL),
(1,'allergies',          'Do you have any known drug allergies?', 'textarea', NULL, 1, 3, 9, NULL, NULL),
(1,'alcohol_units',      'How many units of alcohol do you consume per week?', 'radio', '["0 (none)","1–7 units","8–14 units","15–21 units","22 or more"]', 1, 3, 10, '1 unit = half a pint of beer or a small (125ml) glass of wine.', NULL),
(1,'eating_disorder',    'Have you ever been diagnosed with or treated for an eating disorder?', 'boolean', NULL, 1, 4, 11, NULL, '{"answer":"true"}'),
(1,'pregnancy',          'Are you currently pregnant or planning to become pregnant?', 'boolean', NULL, 1, 4, 12, NULL, '{"answer":"true"}'),
(1,'understand_terms',   'I understand that weight loss medication may have side effects and that results are not guaranteed.', 'boolean', NULL, 1, 4, 13, NULL, '{"answer":"false"}');

-- Template 2: Erectile Dysfunction (FULL)
INSERT INTO `questionnaire_templates` (`condition_id`,`version`,`title`,`description`) VALUES
(2, 1, 'Erectile Dysfunction Assessment', 'This completely confidential questionnaire enables our prescribers to recommend the safest, most suitable ED treatment for you.');

INSERT INTO `questionnaire_questions`
  (`template_id`,`question_key`,`question_text`,`question_type`,`options_json`,`is_required`,`step_number`,`sort_order`,`help_text`,`disqualify_if`) VALUES
(2,'ed_duration',        'How long have you been experiencing difficulties with erections?', 'radio', '["Less than 3 months","3–6 months","6–12 months","More than 1 year","More than 5 years"]', 1, 1, 1, NULL, NULL),
(2,'ed_severity',        'How would you describe the severity of your ED?', 'radio', '["Occasional difficulty","Difficulty more than half the time","Almost never able to achieve or maintain an erection","Never able to achieve an erection"]', 1, 1, 2, NULL, NULL),
(2,'morning_erections',  'Do you experience morning erections?', 'radio', '["Yes, regularly","Yes, occasionally","Rarely","Never"]', 1, 1, 3, NULL, NULL),
(2,'cardiovascular',     'Have you ever been diagnosed with any of the following?', 'checkbox', '["Heart attack","Stroke","Angina or chest pain","Irregular heartbeat (arrhythmia)","None of the above"]', 1, 2, 4, 'Select all that apply.', NULL),
(2,'nitrates',           'Are you currently taking any nitrate medication (e.g. GTN spray, isosorbide)?', 'boolean', NULL, 1, 2, 5, 'PDE5 inhibitors such as sildenafil and tadalafil cannot be used with nitrates — this is a serious safety concern.', '{"answer":"true"}'),
(2,'blood_pressure',     'What is your blood pressure status?', 'radio', '["Normal — no known issues","High blood pressure, currently controlled with medication","High blood pressure, uncontrolled","Low blood pressure","Not sure"]', 1, 2, 6, NULL, NULL),
(2,'diabetes',           'Do you have diabetes?', 'radio', '["No","Type 1 diabetes","Type 2 diabetes","Pre-diabetic"]', 1, 2, 7, NULL, NULL),
(2,'current_medications','Please list all medications you are currently taking.', 'textarea', NULL, 1, 3, 8, 'Include all prescription, over-the-counter medicines and supplements.', NULL),
(2,'allergies',          'Do you have any known drug allergies?', 'textarea', NULL, 1, 3, 9, NULL, NULL),
(2,'liver_kidney',       'Do you have any liver or kidney disease?', 'boolean', NULL, 1, 3, 10, NULL, NULL),
(2,'vision_loss',        'Have you ever experienced sudden or unexplained vision loss?', 'boolean', NULL, 1, 3, 11, 'In rare cases, PDE5 inhibitors have been associated with vision changes.', NULL),
(2,'gp_aware',           'Is your GP aware you are seeking treatment for ED?', 'boolean', NULL, 1, 4, 12, NULL, NULL),
(2,'confirm_accuracy',   'I confirm that all information provided is accurate and complete to the best of my knowledge.', 'boolean', NULL, 1, 4, 13, NULL, '{"answer":"false"}');

-- Stub templates (minimal — 2 questions each so the system doesn't break)
INSERT INTO `questionnaire_templates` (`condition_id`,`version`,`title`,`description`) VALUES
(3, 1, 'Hair Loss Assessment (Men)',     'Coming soon — our clinical team is finalising this questionnaire.'),
(4, 1, 'Digestive Health Assessment',    'Coming soon — our clinical team is finalising this questionnaire.'),
(5, 1, 'Hair Loss Assessment (Women)',   'Coming soon — our clinical team is finalising this questionnaire.'),
(6, 1, 'Skin Health Assessment',         'Coming soon — our clinical team is finalising this questionnaire.');

INSERT INTO `questionnaire_questions` (`template_id`,`question_key`,`question_text`,`question_type`,`is_required`,`step_number`,`sort_order`) VALUES
(3,'stub_current_medications','Please list any current medications.',   'textarea',1,1,1),
(3,'stub_allergies',          'Do you have any known drug allergies?',  'textarea',1,1,2),
(4,'stub_current_medications','Please list any current medications.',   'textarea',1,1,1),
(4,'stub_allergies',          'Do you have any known drug allergies?',  'textarea',1,1,2),
(5,'stub_current_medications','Please list any current medications.',   'textarea',1,1,1),
(5,'stub_allergies',          'Do you have any known drug allergies?',  'textarea',1,1,2),
(6,'stub_current_medications','Please list any current medications.',   'textarea',1,1,1),
(6,'stub_allergies',          'Do you have any known drug allergies?',  'textarea',1,1,2);

-- ============================================================
-- DEFAULT STAFF ACCOUNTS
-- All passwords: PrescribeCo@2024!  (CHANGE IMMEDIATELY)
-- Hash generated with password_hash('PrescribeCo@2024!', PASSWORD_BCRYPT, ['cost'=>12])
-- ============================================================
INSERT INTO `users` (`email`,`password_hash`,`role`,`first_name`,`last_name`,`is_active`,`email_verified`) VALUES
('admin@prescribeandco.co.uk',
 '$2y$12$LCMSv6HJZUA7XBGSInBbgOtlqaGLqJoNqb1XxEpGGqLxmN.bczxuK',
 'admin','System','Administrator',1,1),
('dr.patel@prescribeandco.co.uk',
 '$2y$12$LCMSv6HJZUA7XBGSInBbgOtlqaGLqJoNqb1XxEpGGqLxmN.bczxuK',
 'prescriber','Dr Priya','Patel',1,1),
('dispenser@prescribeandco.co.uk',
 '$2y$12$LCMSv6HJZUA7XBGSInBbgOtlqaGLqJoNqb1XxEpGGqLxmN.bczxuK',
 'dispenser','James','Chen',1,1);

-- NOTE: Hash above is for 'PrescribeCo@2024!' — CHANGE ALL PASSWORDS IMMEDIATELY AFTER FIRST LOGIN
-- ============================================================
--  Prescribe & Co. — Database Additions (v2)
--  Run AFTER the base database.sql has been imported
-- ============================================================

USE `prescribeco_db`;

-- ── Contact messages ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(200) NOT NULL,
  `email`      VARCHAR(191) NOT NULL,
  `subject`    VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Add image_path columns ───────────────────────────────────
ALTER TABLE `products`
  ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(255) DEFAULT NULL AFTER `image_url`;

ALTER TABLE `conditions`
  ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(255) DEFAULT NULL AFTER `icon`;

-- ── Extra settings ───────────────────────────────────────────
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_tagline',         'Expert prescriptions, delivered.',    'Site tagline'),
('contact_email',        'hello@prescribeandco.co.uk',          'Contact form recipient (display only)'),
('max_upload_size_mb',   '5',                                   'Max image upload size in MB'),
('allowed_image_types',  'jpg,jpeg,png,webp',                   'Allowed image extensions'),
('orders_enabled',       '1',                                   'Allow patients to place orders'),
('repeat_rx_enabled',    '1',                                   'Allow repeat prescription requests'),
('maintenance_mode',     '0',                                   'Put site in maintenance mode');
