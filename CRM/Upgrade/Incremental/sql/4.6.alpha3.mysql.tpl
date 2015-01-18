{* file to handle db changes in 4.6.alpha3 during upgrade *}

-- update permission for editing message templates (CRM-15819)

SELECT @messages_menu_id := id FROM civicrm_navigation WHERE name = 'Mailings';

UPDATE `civicrm_navigation` 
SET `permission` = 'edit message templates'
WHERE `parent_id` = @messages_menu_id
AND name = 'Message Templates';
