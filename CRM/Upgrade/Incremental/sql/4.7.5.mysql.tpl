{* file to handle db changes in 4.7.5 during upgrade *}
{include file='../CRM/Upgrade/4.7.5.msg_template/civicrm_msg_template.tpl'}

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

-- CRM-18327 filter value missed on the contact deleted by merge activity --
UPDATE civicrm_option_value ov
LEFT JOIN civicrm_option_group og ON og.id = ov.option_group_id
SET filter = 1
WHERE ov.name = 'Contact Deleted by Merge' AND og.name = 'activity_type';

-- CRM-18241 Change field length of civicrm_option_value.label from 255 to 512 --
{if $multilingual}
  {foreach from=$locales item=loc}
    ALTER TABLE civicrm_option_value CHANGE label_{$loc} label_{$loc} varchar( 512 ) DEFAULT NULL ;
  {/foreach}
{else}
  ALTER TABLE civicrm_option_value CHANGE label label varchar( 512 ) DEFAULT NULL ;
{/if}

-- CRM-18345: Don't delete mailing records when email address / phone is deleted
ALTER TABLE `civicrm_mailing_event_queue`
  DROP FOREIGN KEY `FK_civicrm_mailing_event_queue_email_id`;
  
ALTER TABLE `civicrm_mailing_event_queue`
  ADD CONSTRAINT `FK_civicrm_mailing_event_queue_email_id`
  FOREIGN KEY (`email_id`)
  REFERENCES `civicrm_email`(`id`)
  ON DELETE SET NULL
  ON UPDATE RESTRICT;
  
ALTER TABLE `civicrm_mailing_event_queue`
  DROP FOREIGN KEY `FK_civicrm_mailing_event_queue_phone_id`;
  
ALTER TABLE `civicrm_mailing_event_queue`
  ADD CONSTRAINT `FK_civicrm_mailing_event_queue_phone_id`
  FOREIGN KEY (`phone_id`)
  REFERENCES `civicrm_phone`(`id`)
  ON DELETE SET NULL
  ON UPDATE RESTRICT;

ALTER TABLE `civicrm_mailing_recipients`
  DROP FOREIGN KEY `FK_civicrm_mailing_recipients_email_id`;
  
ALTER TABLE `civicrm_mailing_recipients`
  ADD CONSTRAINT `FK_civicrm_mailing_recipients_email_id`
  FOREIGN KEY (`email_id`)
  REFERENCES `civicrm_email`(`id`)
  ON DELETE SET NULL
  ON UPDATE RESTRICT;
  
ALTER TABLE `civicrm_mailing_recipients`
  DROP FOREIGN KEY `FK_civicrm_mailing_recipients_phone_id`;
  
ALTER TABLE `civicrm_mailing_recipients`
  ADD CONSTRAINT `FK_civicrm_mailing_recipients_phone_id`
  FOREIGN KEY (`phone_id`)
  REFERENCES `civicrm_phone`(`id`)
  ON DELETE SET NULL
  ON UPDATE RESTRICT;
