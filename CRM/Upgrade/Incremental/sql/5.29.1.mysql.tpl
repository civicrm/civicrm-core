{* file to handle db changes in 5.29.1 during upgrade *}

UPDATE `civicrm_custom_field` SET `serialize` = 0 WHERE `serialize` IS NULL;

ALTER TABLE `civicrm_custom_field`
CHANGE COLUMN `serialize`
`serialize` int unsigned NOT NULL DEFAULT 0 COMMENT 'Serialization method - a non-zero value indicates a multi-valued field.';
