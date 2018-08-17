{* file to handle db changes in 5.6.alpha1 during upgrade *}

ALTER TABLE civicrm_prevnext_cache
  CHANGE `entity_id2` `entity_id2` int unsigned NULL   COMMENT 'FK to entity table specified in entity_table column.';
