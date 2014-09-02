{* file to handle db changes in 4.5.0 during upgrade *}
UPDATE `civicrm_dashboard` SET `permission` = 'access my cases and activities,access all cases and activities', `permission_operator` = 'OR' WHERE `name` = 'casedashboard';
