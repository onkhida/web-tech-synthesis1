-- AFA Match Center - Full Expanded Database Schema & Seed Data
-- Ashesi Football Association Live Match Logger & Scoreboard

CREATE DATABASE IF NOT EXISTS `afa_match_center` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `afa_match_center`;

-- Drop existing tables in reverse dependency order
DROP TABLE IF EXISTS `event_logs`;
DROP TABLE IF EXISTS `match_rosters`;
DROP TABLE IF EXISTS `matches`;
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `teams`;

-- 1. Teams Table
CREATE TABLE `teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `short_code` VARCHAR(10) NOT NULL,
  `hex_color` VARCHAR(10) NOT NULL DEFAULT '#333333',
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Players Table
CREATE TABLE `players` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `jersey_number` INT NOT NULL,
  `position` ENUM('GK', 'DEF', 'MID', 'FWD') NOT NULL DEFAULT 'MID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Matches Table
CREATE TABLE `matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `home_team_id` INT NOT NULL,
  `away_team_id` INT NOT NULL,
  `home_score` INT DEFAULT 0,
  `away_score` INT DEFAULT 0,
  `status` ENUM('scheduled', 'live', 'completed') DEFAULT 'live',
  `match_minute` INT DEFAULT 0,
  `match_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`home_team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Match Rosters Table (Starting Lineups & Squad for a Match)
CREATE TABLE `match_rosters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `player_id` INT NOT NULL,
  `is_starter` TINYINT(1) DEFAULT 1,
  `position` ENUM('GK', 'DEF', 'MID', 'FWD') NOT NULL,
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Event Logs Table
CREATE TABLE `event_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `minute` INT NOT NULL,
  `player_id` INT DEFAULT NULL,
  `player_name` VARCHAR(100) NOT NULL,
  `event_type` ENUM('goal', 'yellow_card', 'red_card', 'sub') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- SEED DATA
-- =========================================================================

-- Seed Teams
INSERT INTO `teams` (`id`, `name`, `short_code`, `hex_color`, `logo_url`) VALUES
(1, 'Red Army FC', 'RED', '#D9232A', 'assets/red_army.png'),
(2, 'Highlanders FC', 'HLD', '#B5944D', 'assets/highlanders.png'),
(3, 'Legends UTD', 'LGD', '#E85D64', 'assets/legends.png'),
(4, 'Elite FC', 'ELT', '#1E40AF', 'assets/elite.png');

-- Seed Players for Red Army FC (Team 1)
INSERT INTO `players` (`id`, `team_id`, `name`, `jersey_number`, `position`) VALUES
(1, 1, 'Kofi Mensah', 10, 'FWD'),
(2, 1, 'Yaw Addo', 7, 'MID'),
(3, 1, 'Kwaku Baah', 1, 'GK'),
(4, 1, 'Samuel Boateng', 4, 'DEF'),
(5, 1, 'Daniel Owusu', 5, 'DEF'),
(6, 1, 'Michael Amoah', 8, 'MID');

-- Seed Players for Highlanders FC (Team 2)
INSERT INTO `players` (`id`, `team_id`, `name`, `jersey_number`, `position`) VALUES
(7, 2, 'Kwame Osei', 9, 'FWD'),
(8, 2, 'Emmanuel Appiah', 11, 'MID'),
(9, 2, 'Joseph Darko', 1, 'GK'),
(10, 2, 'Isaac Kwarteng', 3, 'DEF'),
(11, 2, 'Benjamin Yeboah', 6, 'MID'),
(12, 2, 'Francis Gyan', 2, 'DEF');

-- Seed Players for Legends UTD (Team 3)
INSERT INTO `players` (`id`, `team_id`, `name`, `jersey_number`, `position`) VALUES
(13, 3, 'David Tetteh', 10, 'FWD'),
(14, 3, 'Eric Quaye', 8, 'MID'),
(15, 3, 'Charles Asare', 1, 'GK');

-- Seed Players for Elite FC (Team 4)
INSERT INTO `players` (`id`, `team_id`, `name`, `jersey_number`, `position`) VALUES
(16, 4, 'Prince Mensah', 7, 'FWD'),
(17, 4, 'Felix Agyeman', 4, 'DEF'),
(18, 4, 'Gideon Antwi', 1, 'GK');

-- Seed Live Match #1 (Red Army FC vs Highlanders FC)
INSERT INTO `matches` (`id`, `home_team_id`, `away_team_id`, `home_score`, `away_score`, `status`, `match_minute`) VALUES
(1, 1, 2, 2, 1, 'live', 62);

-- Seed Match Lineups / Rosters for Match #1
INSERT INTO `match_rosters` (`match_id`, `team_id`, `player_id`, `is_starter`, `position`) VALUES
-- Red Army Starting XI
(1, 1, 3, 1, 'GK'),
(1, 1, 4, 1, 'DEF'),
(1, 1, 5, 1, 'DEF'),
(1, 1, 2, 1, 'MID'),
(1, 1, 6, 1, 'MID'),
(1, 1, 1, 1, 'FWD'),
-- Highlanders Starting XI
(1, 2, 9, 1, 'GK'),
(1, 2, 10, 1, 'DEF'),
(1, 2, 12, 1, 'DEF'),
(1, 2, 8, 1, 'MID'),
(1, 2, 11, 1, 'MID'),
(1, 2, 7, 1, 'FWD');

-- Seed Event Logs for Match #1
INSERT INTO `event_logs` (`match_id`, `team_id`, `minute`, `player_id`, `player_name`, `event_type`) VALUES
(1, 1, 14, 1, 'Kofi Mensah', 'goal'),
(1, 2, 28, 7, 'Kwame Osei', 'yellow_card'),
(1, 2, 39, 8, 'Emmanuel Appiah', 'goal'),
(1, 1, 45, 1, 'Kofi Mensah', 'goal'),
(1, 1, 52, 2, 'Yaw Addo', 'sub');
