-- Cremoria Database Schema
-- Reconstructed from ORM models, controllers, and libraries.
-- Import: mysql -u root -p cremoria < application/schema.sql

SET NAMES utf8;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- roles
-- Required before users due to roles_users FK.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(32)  NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- users
-- Auth_User_Model (auth module) + game-specific columns.
-- currentResearch / researchTime set to NULL by TickEconomy
-- when research completes.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email           VARCHAR(127) NOT NULL,
  username        VARCHAR(32)  NOT NULL,
  password        VARCHAR(64)  NOT NULL,
  regKey          VARCHAR(64)  NULL,
  currentResearch INT          NULL,
  researchTime    INT          NULL,
  researchPts     INT          NOT NULL DEFAULT 0,
  currentRscPts   INT          NOT NULL DEFAULT 0,
  buildingPts     INT          NOT NULL DEFAULT 0,
  totalPts        INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email    (email),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- roles_users
-- HABTM junction for users <-> roles.
-- Kohana Auth convention: plural(role) + _ + plural(user).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS roles_users (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_roles_users_role_id (role_id),
  CONSTRAINT fk_roles_users_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_roles_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- user_tokens
-- Auth_User_Token_Model. Column name 'userAgent' (camelCase)
-- matches the PHP model property assignment exactly.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_tokens (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id   INT UNSIGNED NOT NULL,
  userAgent VARCHAR(40)  NOT NULL,
  token     CHAR(32)     NOT NULL,
  created   INT UNSIGNED NOT NULL,
  expires   INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_tokens_token (token),
  KEY idx_user_tokens_user_id (user_id),
  CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- towns
-- buildingTime / currentBuilding are nullable: TickEconomy
-- sets them to NULL explicitly when a build finishes.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS towns (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  name            VARCHAR(100) NOT NULL,
  x               INT          NOT NULL DEFAULT 0,
  y               INT          NOT NULL DEFAULT 0,
  gold            INT          NOT NULL DEFAULT 0,
  wood            INT          NOT NULL DEFAULT 0,
  stone           INT          NOT NULL DEFAULT 0,
  iron            INT          NOT NULL DEFAULT 0,
  goldInc         INT          NOT NULL DEFAULT 0,
  woodInc         INT          NOT NULL DEFAULT 0,
  stoneInc        INT          NOT NULL DEFAULT 0,
  ironInc         INT          NOT NULL DEFAULT 0,
  ironIncMax      INT          NOT NULL DEFAULT 0,
  buildingTime    INT          NULL,
  currentBuilding INT          NULL,
  currentBldPts   INT          NOT NULL DEFAULT 0,
  buildingPts     INT          NOT NULL DEFAULT 0,
  unitCapUsed     INT          NOT NULL DEFAULT 0,
  unitCap         INT          NOT NULL DEFAULT 0,
  movCap          INT          NOT NULL DEFAULT 0,
  movCapUsed      INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_towns_user_id (user_id),
  CONSTRAINT fk_towns_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- buildings
-- Composite PK: (id=building_type_id, town_id).
-- Writes use: INSERT ... ON DUPLICATE KEY UPDATE quantity = quantity + 1
-- No FK on town_id: composite PK tables enforce integrity at app layer.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS buildings (
  id       INT          NOT NULL,
  town_id  INT UNSIGNED NOT NULL,
  quantity INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id, town_id),
  KEY idx_buildings_town_id (town_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- units
-- Composite PK: (unit_id, town_id).
-- Writes use: INSERT ... ON DUPLICATE KEY UPDATE quantity = quantity + N
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS units (
  unit_id  INT          NOT NULL,
  town_id  INT UNSIGNED NOT NULL,
  quantity INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (unit_id, town_id),
  KEY idx_units_town_id (town_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- techs
-- Composite PK: (id=tech_type_id, user_id).
-- Writes use: INSERT ... ON DUPLICATE KEY UPDATE quantity = quantity + 1
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS techs (
  id       INT          NOT NULL,
  user_id  INT UNSIGNED NOT NULL,
  quantity INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id, user_id),
  KEY idx_techs_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- unit_productions
-- Rows are deleted by TickEconomy when time <= 0 and units
-- have been credited to the town.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS unit_productions (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  unit_id  INT          NOT NULL,
  town_id  INT UNSIGNED NOT NULL,
  quantity INT          NOT NULL DEFAULT 0,
  time     INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_unit_productions_town_id (town_id),
  CONSTRAINT fk_unit_productions_town FOREIGN KEY (town_id) REFERENCES towns (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- movements
-- 'start' is nullable: makeReturnMovement() never sets it,
-- relying on startX/startY coordinates instead.
-- action values: 0=Transfer, 1=Transport, 2=Attack,
--                3=Espionage, 4=Return
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movements (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner    INT UNSIGNED NOT NULL,
  start    INT          NULL,
  startX   INT          NOT NULL DEFAULT 0,
  startY   INT          NOT NULL DEFAULT 0,
  destX    INT          NOT NULL DEFAULT 0,
  destY    INT          NOT NULL DEFAULT 0,
  gold     INT          NOT NULL DEFAULT 0,
  wood     INT          NOT NULL DEFAULT 0,
  stone    INT          NOT NULL DEFAULT 0,
  iron     INT          NOT NULL DEFAULT 0,
  action   INT          NOT NULL DEFAULT 0,
  duration INT          NOT NULL DEFAULT 0,
  arrival  INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_movements_owner        (owner),
  KEY idx_movements_dest_coords  (destX, destY),
  KEY idx_movements_start_coords (startX, startY),
  CONSTRAINT fk_movements_owner FOREIGN KEY (owner) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- troops
-- ON DELETE CASCADE: troops are always scoped to a movement.
-- TickMovement also manually deletes them, but CASCADE ensures
-- no orphans if a movement is removed by other means.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS troops (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  movement_id INT UNSIGNED NOT NULL,
  unit_id     INT          NOT NULL,
  quantity    INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_troops_movement_id (movement_id),
  CONSTRAINT fk_troops_movement FOREIGN KEY (movement_id) REFERENCES movements (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- messages
-- 'sender' has no FK: value 0 is used for system messages
-- (e.g. espionage reports). Enforcing a FK would break this.
-- 'unread' DEFAULT 1: send() never sets the column, so the
-- DB default must mark new messages as unread.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender   INT          NOT NULL,
  receiver INT UNSIGNED NOT NULL,
  title    VARCHAR(255) NOT NULL,
  text     TEXT         NOT NULL,
  unread   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_messages_receiver (receiver),
  CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET foreign_key_checks = 1;

-- ------------------------------------------------------------
-- Seed data: auth roles
-- Required for the registration flow to work.
-- register.php: $newUser->add(ORM::factory('role', 'unverified'))
-- verify.php promotes users to 'login' role.
-- ------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
  ('login',      'Verified account — may log in'),
  ('unverified', 'Registered but awaiting email verification');

-- ------------------------------------------------------------
-- Seed data: test account
-- Credentials: username=test  password=test
-- Hash generated with sha1 + salt_pattern '1,3,5,9,14,15,20,21,28,30'
-- ------------------------------------------------------------
INSERT INTO users (email, username, password) VALUES
  ('test@example.com', 'test', 'aa55b89c63efd1a142ecf9cdb4gbh0cc5722i6ajae0870d547');

INSERT INTO roles_users (user_id, role_id)
  SELECT u.id, r.id FROM users u, roles r
  WHERE u.username = 'test' AND r.name = 'login';

INSERT INTO towns (user_id, name, x, y, gold, wood, stone, iron,
                   goldInc, woodInc, stoneInc, ironInc, ironIncMax,
                   unitCap, movCap)
  SELECT id, 'Testburg', 5, 5, 500, 500, 500, 500,
         10, 10, 10, 10, 100,
         20, 5
  FROM users WHERE username = 'test';
