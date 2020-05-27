{* file to handle db changes in 4.6.6 during upgrade *}

-- CRM-16846 - This upgrade may have been previously skipped so moving it to 4.6.6
-- update permission for editing message templates (CRM-15819)

SELECT @messages_menu_id := id FROM civicrm_navigation WHERE name = 'Mailings';

UPDATE `civicrm_navigation`
SET `permission` = 'edit message templates'
WHERE `parent_id` = @messages_menu_id
AND name = 'Message Templates';
