{* file to handle db changes in 6.19.alpha1 during upgrade *}

ALTER TABLE `civicrm_tag` ADD COLUMN `weight` int NOT NULL DEFAULT 0 COMMENT 'Ordering weight among sibling tags.';
