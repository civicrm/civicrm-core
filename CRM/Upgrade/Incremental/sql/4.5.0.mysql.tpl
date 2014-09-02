{* file to handle db changes in 4.5.0 during upgrade *}

-- CRM-15211
UPDATE `civicrm_dashboard` SET `permission` = 'access my cases and activities,access all cases and activities', `permission_operator` = 'OR' WHERE `name` = 'casedashboard';

-- CRM-15218
UPDATE `civicrm_uf_group` SET name = LOWER(name) WHERE name IN ("New_Individual", "New_Organization", "New_Household");
