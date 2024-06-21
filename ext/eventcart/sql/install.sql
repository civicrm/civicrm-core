  CREATE TABLE IF NOT EXISTS `civicrm_event_carts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Cart ID',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_contact who created this cart',
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_event_carts_user_id` (`user_id`),
  CONSTRAINT `FK_civicrm_event_carts_user_id` FOREIGN KEY (`user_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `civicrm_events_in_carts` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Event In Cart ID',
`event_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Event ID',
`event_cart_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Event Cart ID',
PRIMARY KEY (`id`),
KEY `FK_civicrm_events_in_carts_event_id` (`event_id`),
KEY `FK_civicrm_events_in_carts_event_cart_id` (`event_cart_id`),
CONSTRAINT `FK_civicrm_events_in_carts_event_cart_id` FOREIGN KEY (`event_cart_id`) REFERENCES `civicrm_event_carts` (`id`) ON DELETE CASCADE,
CONSTRAINT `FK_civicrm_events_in_carts_event_id` FOREIGN KEY (`event_id`) REFERENCES `civicrm_event` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE civicrm_participant ADD CONSTRAINT
  `FK_civicrm_participant_cart_id`
  FOREIGN KEY (`cart_id`)
    REFERENCES `civicrm_event_carts`(`ID`);
