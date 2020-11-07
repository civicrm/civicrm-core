{* file to handle db changes in 5.32.alpha1 during upgrade *}

UPDATE civicrm_job SET name = 'Update relationship status based on dates',
description = ' Enables relationships where start date <  = today (ie those relationships whose start date is today / in the past).
Disables relationships that have expired (ie. those relationships whose end date is in the past).'
WHERE api_action = 'disable_expired_relationships';

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
