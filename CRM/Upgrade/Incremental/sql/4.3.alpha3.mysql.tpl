{include file='../CRM/Upgrade/4.3.alpha3.msg_template/civicrm_msg_template.tpl'}
-- CRM-11906

ALTER TABLE `civicrm_batch` CHANGE `item_count` `item_count` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'Number of items in a batch.';