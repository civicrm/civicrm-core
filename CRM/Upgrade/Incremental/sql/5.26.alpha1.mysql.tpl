{* file to handle db changes in 5.26.alpha1 during upgrade *}

ALTER TABLE civicrm_option_value MODIFY COLUMN `filter` int unsigned DEFAULT NULL COMMENT 'Bitwise logic can be used to create subsets of options within an option_group for different uses.';

UPDATE civicrm_contact SET is_deceased = 0 WHERE is_deceased IS NULL;
ALTER TABLE civicrm_contact MODIFY COLUMN is_deceased TINYINT NOT NULL DEFAULT 0;

-- Update Colmbra state/province to Coimbra
UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Colmbra'
SET s.name = 'Coimbra';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Braganca'
SET s.name = 'Bragança';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Ovora'
SET s.name = 'Évora';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Santarem'
SET s.name = 'Santarém';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Setubal'
SET s.name = 'Setúbal';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Regiao Autonoma dos Acores'
SET s.name = 'Região Autónoma dos Açores';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Portugal'
     AND s.name = 'Regiao Autonoma da Madeira'
SET s.name = 'Região Autónoma da Madeira';
