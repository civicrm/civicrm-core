{* file to handle db changes in 4.7.13 during upgrade *}

-- CRM-15371 Manage tags with new *manage tags* permission (used to need *administer CiviCRM* permission)

UPDATE civicrm_navigation SET
  `url` = 'civicrm/tag?reset=1&action=add',
  `permission` = 'manage tags'
WHERE `name` = 'Manage Tags (Categories)';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/admin/tag?reset=1',
  `permission` = 'manage tags'
WHERE `name` = 'New Tag';

UPDATE civicrm_navigation SET
  `url` = 'civicrm/admin/tag?reset=1'
WHERE `name` = 'Tags (Categories)';
