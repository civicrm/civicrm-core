CREATE TABLE `civicrm_session` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique Session ID',
  `session_id` char(64) NOT NULL COMMENT 'Hexadecimal Session Identifier',
  `data` longtext COMMENT 'Session Data',
  `last_accessed` datetime COMMENT 'Timestamp of the last session access',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `index_session_id`(session_id)
)
ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
