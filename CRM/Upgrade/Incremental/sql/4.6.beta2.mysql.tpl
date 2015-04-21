{* file to handle db changes in 4.6.beta2 during upgrade *}
-- CRM-16018
ALTER TABLE  `civicrm_membership_block` CHANGE  `membership_types`  `membership_types` VARCHAR( 1024 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci COMMENT  'Membership types to be exposed by this block.';

-- CRM-15578 Require access CiviMail permission for A/B Testing feature
UPDATE civicrm_navigation
SET permission = 'access CivMail', permission_operator = ''
WHERE name = 'New A/B Test' OR name = 'Manage A/B Tests';
