-- AFA Match Center Database Schema & Seed Data
-- Ashesi Football Association Live Match Logger & Scoreboard

CREATE DATABASE IF NOT EXISTS `afa_match_center` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `afa_match_center`;

-- Drop existing tables if re-executing
DROP TABLE IF EXISTS `event_logs`;
DROP TABLE IF EXISTS `matches`;
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

-- 2. Matches Table
CREATE TABLE `matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `home_team_id` INT NOT NULL,
  `away_team_id` INT NOT NULL,
  `home_score` INT DEFAULT 0,
  `away_score` INT DEFAULT 0,
  `status` ENUM('scheduled', 'live', 'completed') DEFAULT 'live',
  `match_minute` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`home_team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`away_team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Event Logs Table
CREATE TABLE `event_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `minute` INT NOT NULL,
  `player_name` VARCHAR(100) NOT NULL,
  `event_type` ENUM('goal', 'yellow_card', 'red_card', 'sub') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data: 4 Teams
INSERT INTO `teams` (`id`, `name`, `short_code`, `hex_color`, `logo_url`) VALUES
(1, 'Red Army FC', 'RED', '#D9232A', 'assets/red_army.png'),
(2, 'Highlanders FC', 'HLD', '#B5944D', 'assets/highlanders.png'),
(3, 'Legends UTD', 'LGD', '#E85D64', 'assets/legends.png'),
(4, 'Elite FC', 'ELT', '#1E40AF', 'assets/elite.png');

-- Seed Data: 1 Live Match between Red Army FC and Highlanders FC
INSERT INTO `matches` (`id`, `home_team_id`, `away_team_id`, `home_score`, `away_score`, `status`, `match_minute`) VALUES
(1, 1, 2, 2, 1, 'live', 58);

-- Seed Data: Sample Initial Events for Match #1
INSERT INTO `event_logs` (`match_id`, `team_id`, `minute`, `player_name`, `event_type`) VALUES
(1, 1, 14, 'Kofi Mensah', 'goal'),
(1, 2, 28, 'Kwame Osei', 'yellow_card'),
(1, 2, 39, 'Emmanuel Appiah', 'goal'),
(1, 1, 45, 'Kofi Mensah', 'goal'),
(1, 1, 52, 'Yaw Addo', 'sub');
