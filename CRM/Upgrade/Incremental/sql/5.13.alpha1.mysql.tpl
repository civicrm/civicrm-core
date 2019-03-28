{* file to handle db changes in 5.13.alpha1 during upgrade *}

-- dev/core#829 Swaziland has changed its name to Eswatini
UPDATE civicrm_country SET name = 'Eswatini' WHERE name = 'Swaziland';
UPDATE civicrm_currency SET full_name = 'Eswatini Lilangeni' WHERE name = 'SZL';
