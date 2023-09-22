{* file to handle db changes in 5.39.alpha1 during upgrade *}

CREATE TABLE IF NOT EXISTS `civicrm_translation` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique String ID',
  `entity_table` varchar(64) NOT NULL COMMENT 'Table where referenced item is stored',
  `entity_field` varchar(64) NOT NULL COMMENT 'Field where referenced item is stored',
  `entity_id` int NOT NULL COMMENT 'ID of the relevant entity.',
  `language` varchar(5) NOT NULL COMMENT 'Relevant language',
  `status_id` tinyint NOT NULL DEFAULT 1 COMMENT 'Specify whether the string is active, draft, etc',
  `string` longtext NOT NULL COMMENT 'Translated string',
  PRIMARY KEY (`id`),
  INDEX `index_entity_lang`(entity_id, entity_table, language)
)
ENGINE=InnoDB ROW_FORMAT=DYNAMIC;
