{* file to handle db changes in 4.5.0 during upgrade *}

{include file='../CRM/Upgrade/4.5.0.msg_template/civicrm_msg_template.tpl'}

ALTER TABLE `civicrm_action_schedule`
  ADD COLUMN `sms_template_id` int(10) unsigned DEFAULT NULL COMMENT 'SMS Reminder Template. FK to id in civicrm_msg_template.' AFTER `msg_template_id`,
  ADD COLUMN `sms_body_text` longtext COLLATE utf8_unicode_ci COMMENT 'Body of the mailing in html format.' AFTER `body_html`,
  ADD CONSTRAINT `FK_civicrm_action_schedule_sms_template_id` FOREIGN KEY (`sms_template_id`) REFERENCES  civicrm_msg_template(`id`) ON DELETE SET NULL;

ALTER TABLE `civicrm_msg_template`
  ADD COLUMN `is_sms` tinyint(4) DEFAULT '0' COMMENT 'Is this message template used for sms?';
