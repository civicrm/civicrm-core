{* file to handle db changes in 6.8.alpha1 during upgrade *}

// https://lab.civicrm.org/dev/core/-/issues/6143
ALTER TABLE `civicrm_translation_source` ADD CONSTRAINT `UI_source_key` UNIQUE (source_key);
ALTER TABLE `civicrm_translation_source` DROP INDEX `index_source_key`;
