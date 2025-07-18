-- -----------------------------------------------------
-- Schema for MenteeGo (ACES)
-- Normalised to 3NF
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS menteego CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menteego;

-- ----------------------------
-- Roles
-- ----------------------------
CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(30) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (name) VALUES
 ('admin'),
 ('mentor'),
 ('mentee');

-- ----------------------------
-- Users
-- ----------------------------
CREATE TABLE users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id           INT UNSIGNED NOT NULL,
    first_name        VARCHAR(60) NOT NULL,
    last_name         VARCHAR(60) NOT NULL,
    email             VARCHAR(120) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    bio               TEXT NULL,
    skills            TEXT NULL,
    availability      VARCHAR(120) NULL,
    email_verified_at DATETIME NULL,
    remember_token    VARCHAR(100) NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- Mentor Requests
-- ----------------------------
CREATE TABLE mentor_requests (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mentee_id  INT UNSIGNED NOT NULL,
    mentor_id  INT UNSIGNED NOT NULL,
    status     ENUM('pending','accepted','rejected','cancelled') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_mentee FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_mentor FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_request (mentee_id, mentor_id)
) ENGINE=InnoDB;

-- ----------------------------
-- Mentor–Mentee Matches
-- ----------------------------
CREATE TABLE mentor_matches (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mentee_id  INT UNSIGNED NOT NULL,
    mentor_id  INT UNSIGNED NOT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ended_at   DATETIME NULL,
    CONSTRAINT fk_match_mentee FOREIGN KEY (mentee_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_mentor FOREIGN KEY (mentor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_match (mentee_id)
) ENGINE=InnoDB;

-- ----------------------------
-- Messages
-- ----------------------------
CREATE TABLE messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id  INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    content    TEXT NOT NULL,
    read_at    DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_sender   FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- Notifications
-- ----------------------------
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    message     VARCHAR(255) NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    link        VARCHAR(255) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;