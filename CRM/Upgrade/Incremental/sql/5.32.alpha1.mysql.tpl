{* file to handle db changes in 5.32.alpha1 during upgrade *}

-- update italian provinces (pull request #18859)
UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Italy'
     AND s.abbreviation = 'Bar'
SET s.abbreviation = 'BT';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Italy'
     AND s.abbreviation = 'Fer'
SET s.abbreviation = 'FM';

UPDATE civicrm_state_province s
 INNER JOIN civicrm_country c
   on c.id = s.country_id AND c.name = 'Italy'
     AND s.abbreviation = 'Mon'
SET s.abbreviation = 'MB';
