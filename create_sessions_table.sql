CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` INT(11) NOT NULL
);

CREATE INDEX `sessions_user_id_index` ON `sessions` (`user_id`);
CREATE INDEX `sessions_last_activity_index` ON `sessions` (`last_activity`); 