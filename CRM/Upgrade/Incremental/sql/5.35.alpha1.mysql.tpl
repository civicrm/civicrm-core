{* file to handle db changes in 5.35.alpha1 during upgrade *}

-- Update permissions for CiviCRM Admin Menu -> CiviMail -> Message Templates
UPDATE civicrm_navigation n
SET n.permission = 'edit user-driven message templates,edit message templates,edit system workflow message templates', n.permission_operator = 'OR'
WHERE n.name = 'Message Templates'
AND n.permission = 'edit message templates';
