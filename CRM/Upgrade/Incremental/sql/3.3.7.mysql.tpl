-- CRM-8113 We need a 3.3.7.mysql.tpl file to exist in order for CRM_Upgrade_Incremental_php_ThreeThree::upgrade_3_3_7 to be run

-- CRM-8218, contact dashboard changes
{if $alterContactDashboard}
ALTER TABLE `civicrm_dashboard` DROP `content`, DROP `created_date`;

ALTER TABLE `civicrm_dashboard_contact`  ADD `content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `weight`,  ADD `created_date` DATETIME NULL DEFAULT NULL AFTER `content`;
{/if}
