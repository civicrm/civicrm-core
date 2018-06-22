{* file to handle db changes in 5.4.alpha1 during upgrade *}

ALTER TABLE civicrm_cache MODIFY COLUMN data longblob COMMENT 'data associated with this path';
