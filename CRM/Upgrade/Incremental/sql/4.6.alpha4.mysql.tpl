{* file to handle db changes in 4.6.alpha4 during upgrade *}
--CRM-15821 PCP Owner Notification

{include file='../CRM/Upgrade/4.6.alpha4.msg_template/civicrm_msg_template.tpl'}

-- Add new column owner_notify_id in pcp block table
ALTER TABLE `civicrm_pcp_block` ADD `owner_notify_id` INT NULL DEFAULT NULL COMMENT 'FK to option_value where option_group = pcp_owner_notification';

-- Add new column is_notify in pcp table
ALTER TABLE `civicrm_pcp` ADD `is_notify` INT NOT NULL DEFAULT '0';

--Add PCP owner notification option group
INSERT INTO `civicrm_option_group`  ( `name`, `title`, `is_active` ) VALUES ('pcp_owner_notify', 'PCP owner notifications', 1);

SELECT @ogid_pcp_owner := MAX(id) FROM `civicrm_option_group`;

INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `is_default`, `weight`) VALUES (@ogid_pcp_owner, 'Owner chooses whether to receive notifications', '1', 'owner_chooses', 1, 1), (@ogid_pcp_owner, 'Notifications are sent to ALL owners', '2', 'all_owners', 0, 2),(@ogid_pcp_owner, 'Notifications are NOT available', '3', 'no_notifications', 0, 3);

UPDATE `civicrm_pcp_block` SET `owner_notify_id` = 3;
UPDATE `civicrm_pcp` SET `is_notify` = 0;

