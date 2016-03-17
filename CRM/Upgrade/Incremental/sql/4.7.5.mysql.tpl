{* file to handle db changes in 4.7.5 during upgrade *}
-- Minor fix for CRM-16173, CRM-16831 - change labels, add separator, etc.
SELECT @parent_id := id from `civicrm_navigation` where name = 'System Settings' AND domain_id = {$domainID};
UPDATE `civicrm_navigation` SET `label` = 'Components' where `name` = 'Enable Components' and `parent_id` = @parent_id;

UPDATE
  `civicrm_navigation` AS nav1
  JOIN `civicrm_navigation` AS nav2 ON
  nav1.name = 'Connections'
  AND nav2.name = 'Manage Extensions'
  AND nav1.parent_id = @parent_id
SET
  nav1.weight = nav2.weight,
  nav2.weight = nav1.weight,
  nav2.has_separator = 1,
  nav2.label = 'Extensions';
