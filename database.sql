-- PawHeart Pet Adoption System · MySQL / MariaDB
-- I-import sa phpMyAdmin (Import tab) o: mysql -u root < database.sql

CREATE DATABASE IF NOT EXISTS pawheart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pawheart;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  phone         VARCHAR(30)  NOT NULL DEFAULT '',
  address       VARCHAR(255) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE pets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  species     ENUM('Dog','Cat') NOT NULL,
  breed       VARCHAR(100) NOT NULL,
  age         DECIMAL(4,1) NOT NULL DEFAULT 0,
  gender      ENUM('Male','Female') NOT NULL,
  color       VARCHAR(60)  NOT NULL DEFAULT '',
  location    VARCHAR(100) NOT NULL DEFAULT '',
  emoji       VARCHAR(16)  NOT NULL DEFAULT '🐾',
  status      ENUM('Available','Pending','Adopted') NOT NULL DEFAULT 'Available',
  health      VARCHAR(255) NOT NULL DEFAULT '',
  personality VARCHAR(255) NOT NULL DEFAULT '',
  description TEXT,
  photo       VARCHAR(255) NULL,
  featured    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pets_status (status),
  KEY idx_pets_species (species)
) ENGINE=InnoDB;

CREATE TABLE applications (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id          INT UNSIGNED NOT NULL,
  pet_id           INT UNSIGNED NULL,
  full_name        VARCHAR(120) NOT NULL,
  age              TINYINT UNSIGNED NOT NULL,
  phone            VARCHAR(30)  NOT NULL,
  email            VARCHAR(190) NOT NULL,
  address          VARCHAR(255) NOT NULL,
  home_type        VARCHAR(60)  NOT NULL,
  experience       VARCHAR(60)  NOT NULL,
  reason           TEXT         NOT NULL,
  -- Valid ID na in-upload ng user (nakatago sa storage/ids, admin lang ang may access)
  id_type          VARCHAR(60)  NOT NULL,
  id_file          VARCHAR(100) NOT NULL,
  id_original_name VARCHAR(190) NOT NULL DEFAULT '',
  id_mime          VARCHAR(60)  NOT NULL,
  status           ENUM('Pending','Under Review','Approved','Rejected','Completed') NOT NULL DEFAULT 'Pending',
  admin_remarks    VARCHAR(500) NOT NULL DEFAULT '',
  created_at       DATETIME     NOT NULL,
  reviewed_at      DATETIME     NULL,
  PRIMARY KEY (id),
  KEY idx_app_user (user_id),
  KEY idx_app_pet (pet_id),
  KEY idx_app_status (status),
  CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_app_pet  FOREIGN KEY (pet_id)  REFERENCES pets (id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- Admin account: admin@pawheart.ph / admin123  (palitan agad pag live na!)
INSERT INTO users (id, name, email, phone, address, password_hash, role, is_active, created_at) VALUES
(1, 'Admin', 'admin@pawheart.ph', '', '', '$2y$10$QIYG/biH3bhEeOMr6sC3r.1G5r/OJcnoEy/kXsH.lsxJGDB5l5kqm', 'admin', 1, NOW());

INSERT INTO pets (id, name, species, breed, age, gender, color, location, emoji, status, health, personality, description, photo, featured, created_at) VALUES
(1, 'Luna',  'Cat', 'Siamese',          2, 'Female', 'Cream',         'Bulacan',     '🐱', 'Available', 'Vaccinated, spayed, dewormed',   'Shy at first, then very cuddly',  'Luna loves sunny windowsills and gentle head scratches.',      'images/luna.jpg',  1, NOW()),
(2, 'Max',   'Dog', 'Golden Retriever', 3, 'Male',   'Golden',        'Batangas',    '🐶', 'Available', 'Vaccinated, neutered, healthy',  'Playful, loyal, great with kids', 'Max is a bouncy ball-chaser who gives the best hugs.',         'images/max.jpg',   1, NOW()),
(3, 'Bella', 'Dog', 'Shih Tzu',         1, 'Female', 'White & brown', 'Quezon City', '🐩', 'Pending',   'Vaccinated, microchipped',        'Sweet, calm, loves laps',         'Bella is a fluffy little princess looking for a cozy home.',   'images/bella.jpg', 1, NOW()),
(4, 'Coco',  'Cat', 'Puspin',           4, 'Male',   'Orange',        'Bulacan',     '🐈', 'Available', 'Vaccinated, neutered',            'Chatty, curious, food-motivated', 'Coco will happily tell you about his day. Often.',             'images/coco.jpg',  0, NOW()),
(5, 'Milo',  'Dog', 'Aspin',            6, 'Male',   'Brown',         'Batangas',    '🐕', 'Adopted',   'Fully vaccinated',                'Gentle and wise',                 'Milo found his forever home. Congrats, buddy!',                'images/milo.jpg',  0, NOW()),
(6, 'Daisy', 'Cat', 'Persian',          8, 'Female', 'Grey',          'Quezon City', '😺', 'Available', 'Senior check-up done, healthy',   'Mellow, loves naps',              'Daisy is a calm senior who wants a quiet, loving lap.',        'images/daisy.jpg', 0, NOW());
