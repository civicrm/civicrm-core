{* file to handle db changes in 5.38.alpha1 during upgrade *}

ALTER TABLE civicrm_queue_item MODIFY data LONGTEXT;

UPDATE civicrm_extension
SET full_name = 'org.civicrm.search_kit', name = 'search_kit', file = 'search_kit'
WHERE full_name = 'org.civicrm.search';

UPDATE civicrm_managed
SET module = 'org.civicrm.search_kit'
WHERE module = 'org.civicrm.search';
