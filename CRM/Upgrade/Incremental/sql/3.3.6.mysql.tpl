-- CRM-7455

UPDATE civicrm_navigation 
   SET name = 'Grant Report Detail'
 WHERE name LIKE 'Grant Report (Detail)';

UPDATE civicrm_navigation 
   SET name = 'Grant Report Statistics'
 WHERE name LIKE 'Grant Report (Statistics)' OR name LIKE 'Shows statistics for grants';

SELECT @weight := weight
  FROM civicrm_navigation
 WHERE name LIKE 'Grant Report Detail';

UPDATE civicrm_navigation 
   SET weight = @weight
 WHERE name LIKE 'Grant Report Statistics';