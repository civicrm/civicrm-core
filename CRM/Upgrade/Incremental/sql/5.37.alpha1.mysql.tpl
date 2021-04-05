{* file to handle db changes in 5.37.alpha1 during upgrade *}

DROP VIEW IF EXISTS civicrm_view_case_activity_upcoming;
DROP VIEW IF EXISTS civicrm_view_case_activity_recent;

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'United Kingdom' AND s.name = 'Carmarthenshire' AND s.abbreviation = 'CRF'
 SET s.abbreviation = 'CMN';

ALTER TABLE `civicrm_case_type` CHANGE `is_active`  `is_active` tinyint DEFAULT 1 COMMENT 'Is this case type enabled?';
