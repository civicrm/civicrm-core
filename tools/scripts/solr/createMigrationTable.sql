CREATE TABLE IF NOT EXISTS `civicrm_migration_mapping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique table ID',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Table of the object mapped from slave to master',
  `master_id` int(10) unsigned DEFAULT NULL COMMENT 'ID of the object for master',
  `slave_id` int(10) unsigned DEFAULT NULL COMMENT 'The ID of the object for slave',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=133 ;

