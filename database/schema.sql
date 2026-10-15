-- =============================================================================
--  CSV Repair Toolkit — Database Schema
--  Compatible with MySQL 5.7+ / MariaDB 10.3+
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Database ──────────────────────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `csvtoolkit`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `csvtoolkit`;

-- ── tool_categories ───────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `tool_categories`;
CREATE TABLE `tool_categories` (
    `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)     NOT NULL,
    `slug`        VARCHAR(100)     NOT NULL,
    `description` TEXT,
    `icon`        VARCHAR(100)     DEFAULT NULL,
    `sort_order`  SMALLINT         NOT NULL DEFAULT 0,
    `status`      TINYINT(1)       NOT NULL DEFAULT 1 COMMENT '1=active,0=inactive',
    `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_category_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── tools ─────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `category_id`      INT UNSIGNED  DEFAULT NULL,
    `name`             VARCHAR(120)  NOT NULL,
    `slug`             VARCHAR(120)  NOT NULL,
    `short_description`VARCHAR(300)  DEFAULT NULL,
    `meta_title`       VARCHAR(160)  DEFAULT NULL,
    `meta_description` VARCHAR(320)  DEFAULT NULL,
    `h1`               VARCHAR(200)  DEFAULT NULL,
    `content`          LONGTEXT,
    `instructions`     TEXT,
    `schema_type`      VARCHAR(50)   DEFAULT 'WebApplication',
    `is_popular`       TINYINT(1)    NOT NULL DEFAULT 0,
    `sort_order`       SMALLINT      NOT NULL DEFAULT 0,
    `status`           TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tool_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    CONSTRAINT `fk_tool_category` FOREIGN KEY (`category_id`)
        REFERENCES `tool_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── tool_faqs ─────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `tool_faqs`;
CREATE TABLE `tool_faqs` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tool_id`    INT UNSIGNED NOT NULL,
    `question`   VARCHAR(500) NOT NULL,
    `answer`     TEXT         NOT NULL,
    `sort_order` SMALLINT     NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_faq_tool` (`tool_id`),
    CONSTRAINT `fk_faq_tool` FOREIGN KEY (`tool_id`)
        REFERENCES `tools`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── site_settings ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `site_settings`;
CREATE TABLE `site_settings` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100)  NOT NULL,
    `setting_val` TEXT,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── admin_users ───────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(60)  NOT NULL,
    `email`         VARCHAR(120) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `last_login`    DATETIME     DEFAULT NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admin_username` (`username`),
    UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── contact_messages ──────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(160) NOT NULL,
    `phone`      VARCHAR(30)  DEFAULT NULL,
    `subject`    VARCHAR(300) NOT NULL,
    `message`    TEXT         NOT NULL,
    `ip_address` VARCHAR(45)  DEFAULT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
