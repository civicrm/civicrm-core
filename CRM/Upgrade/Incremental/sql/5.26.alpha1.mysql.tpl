{* file to handle db changes in 5.26.alpha1 during upgrade *}

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;
ALTER TABLE civicrm_contact MODIFY COLUMN is_deceased TINYINT NOT NULL DEFAULT 0;

-- Update Colmbra state/province to Coimbra
UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Colmbra'
SET s.name = 'Coimbra';
