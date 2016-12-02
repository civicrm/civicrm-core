{* file to handle db changes in 4.7.14 during upgrade *}
-- CRM-19616 Fix Manage Tags and New Tag Url issues.
UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1'
WHERE `name` = 'Manage Tags (Categories)'
AND `url` = 'civicrm/tag?reset=1&action=add';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1&action=add'
WHERE `name` = 'New Tag'
AND `url` = 'civicrm/admin/tag?reset=1';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1'
WHERE `name` = 'Tags (Categories)'
AND `url` = 'civicrm/admin/tag?reset=1';

-- Handle message template changes
{include file='../CRM/Upgrade/4.7.14.msg_template/civicrm_msg_template.tpl'}
