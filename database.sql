-- ============================================================
-- ECCM – Ethernet Cable Connection Manager
-- MySQL Database Schema v2 (with permissions)
-- ============================================================

CREATE DATABASE IF NOT EXISTS eccm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eccm_db;

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL UNIQUE,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profiles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    owner_id    INT NOT NULL,
    name        VARCHAR(255) NOT NULL,
    data        LONGTEXT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_owner_profile (owner_id, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profile_permissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    profile_id      INT NOT NULL,
    user_id         INT NOT NULL,
    can_view        TINYINT(1) NOT NULL DEFAULT 1,
    can_patch       TINYINT(1) NOT NULL DEFAULT 0,
    can_add_patch   TINYINT(1) NOT NULL DEFAULT 0,
    can_edit_device TINYINT(1) NOT NULL DEFAULT 0,
    can_add_device  TINYINT(1) NOT NULL DEFAULT 0,
    can_delete      TINYINT(1) NOT NULL DEFAULT 0,
    can_manage      TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_profile_user (profile_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_active_profile (
    user_id     INT PRIMARY KEY,
    profile_id  INT NOT NULL,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_settings (
    user_id     INT PRIMARY KEY,
    settings    TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_subscriptions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    profile_id      INT NOT NULL,
    on_device_change TINYINT(1) NOT NULL DEFAULT 0,
    on_device_add    TINYINT(1) NOT NULL DEFAULT 0,
    on_patch_change  TINYINT(1) NOT NULL DEFAULT 0,
    on_patch_add     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_profile_notif (user_id, profile_id)
) ENGINE=InnoDB;
