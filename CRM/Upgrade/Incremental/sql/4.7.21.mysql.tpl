{* file to handle db changes in 4.7.21 during upgrade *}

-- CRM-20606 (spelling mistake in Austrian provinces)
UPDATE civicrm_state_province
   SET name = 'Niederösterreich'
 WHERE country_id = 1014
   AND name = 'Niederosterreich';

UPDATE civicrm_state_province
   SET name = 'Oberösterreich'
 WHERE country_id = 1014
   AND name = 'Oberosterreich';
