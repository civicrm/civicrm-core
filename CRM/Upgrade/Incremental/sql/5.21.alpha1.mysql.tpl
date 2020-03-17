{* file to handle db changes in 5.21.alpha1 during upgrade *}

-- dev/core#1166 Update Republic of Macedonia name following ISO 3166-1 change
UPDATE civicrm_country SET name = 'North Macedonia' WHERE name = 'Macedonia, Republic of';
