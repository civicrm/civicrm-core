{* file to handle db changes in 6.21.alpha1 during upgrade *}

-- Update ACL menu icon
UPDATE `civicrm_navigation` SET `icon` = 'crm-i fa-users-between-lines' WHERE `name` = 'Permissions (Access Control)';
