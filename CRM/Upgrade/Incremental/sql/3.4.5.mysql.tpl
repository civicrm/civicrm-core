-- CRM-8248
{include file='../CRM/Upgrade/3.4.5.msg_template/civicrm_msg_template.tpl'}

-- CRM-8348

CREATE TABLE IF NOT EXISTS civicrm_action_log (
     id                   int UNSIGNED NOT NULL AUTO_INCREMENT,
     contact_id           int UNSIGNED NULL DEFAULT NULL COMMENT 'FK to Contact ID',
     entity_id            int UNSIGNED NOT NULL COMMENT 'FK to id of the entity that the action was performed on. Pseudo - FK.',
     entity_table         varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'name of the entity table for the above id, e.g. civicrm_activity, civicrm_participant',
     action_schedule_id   int UNSIGNED NOT NULL COMMENT 'FK to the action schedule that this action originated from.',
     action_date_time     DATETIME NULL DEFAULT NULL COMMENT 'date time that the action was performed on.',
     is_error             TINYINT( 4 ) NULL DEFAULT '0' COMMENT 'Was there any error sending the reminder?',
     message              TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Description / text in case there was an error encountered.',
     repetition_number    INT( 10 ) UNSIGNED NULL COMMENT 'Keeps track of the sequence number of this repetition.',
     PRIMARY KEY ( id ),
     CONSTRAINT FK_civicrm_action_log_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE,
     CONSTRAINT FK_civicrm_action_log_action_schedule_id FOREIGN KEY (action_schedule_id) REFERENCES civicrm_action_schedule(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- CRM-8370
ALTER TABLE `civicrm_action_log` CHANGE `repetition_number` `repetition_number` INT( 10 ) UNSIGNED NULL COMMENT 'Keeps track of the sequence number of this repetition.';

-- CRM-8085
UPDATE civicrm_mailing SET domain_id = {$domainID} WHERE domain_id IS NULL;

-- CRM-8402, CRM-8679
DELETE et2.* from civicrm_entity_tag et1 
INNER JOIN civicrm_entity_tag et2 ON et1.entity_table = et2.entity_table AND et1.entity_id = et2.entity_id AND et1.tag_id = et2.tag_id
WHERE et1.id < et2.id;

ALTER TABLE civicrm_entity_tag 
DROP INDEX index_entity;

ALTER TABLE civicrm_entity_tag 
ADD UNIQUE INDEX UI_entity_id_entity_table_tag_id( entity_table, entity_id, tag_id );

-- CRM-8513

SELECT @report_template_gid := MAX(id) FROM civicrm_option_group WHERE name = 'report_template';

{if $multilingual}
   {foreach from=$locales item=locale}
      UPDATE civicrm_option_value SET label_{$locale} = 'Pledge Report (Detail)', description_{$locale} = 'Pledge Report' WHERE option_group_id = @report_template_gid AND value = 'pledge/summary';
   {/foreach}
{else}
      UPDATE civicrm_option_value SET label = 'Pledge Report (Detail)', description = 'Pledge Report' WHERE option_group_id = @report_template_gid AND value = 'pledge/summary';
{/if}

UPDATE civicrm_option_value SET name = 'CRM_Report_Form_Pledge_Detail', value = 'pledge/detail' WHERE option_group_id = @report_template_gid AND value = 'pledge/summary';

UPDATE civicrm_report_instance SET report_id = 'pledge/detail' WHERE report_id = 'pledge/summary';

SELECT @weight              := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @report_template_gid;
SELECT @pledgeCompId        := MAX(id)     FROM civicrm_component where name = 'CiviPledge';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@report_template_gid, {localize}'Pledge Summary Report'{/localize}, 'pledge/summary', 'CRM_Report_Form_Pledge_Summary', @weight := @weight + 1, {localize}'Pledge Summary Report.'{/localize}, 1, @pledgeCompId);

-- CRM-8519
UPDATE civicrm_payment_processor 
SET `url_site` = 'https://sec.paymentexpress.com/pxpay/pxpay.aspx' 
WHERE `url_site` = 'https://www.paymentexpress.com/pxpay/pxpay.aspx' 
OR url_site = 'https://sec2.paymentexpress.com/pxpay/pxpay.aspx';

UPDATE civicrm_payment_processor 
SET `url_site` = 'https://sec.paymentexpress.com/pxpay/pxaccess.aspx' 
WHERE `url_site` = 'https://www.paymentexpress.com/pxpay/pxaccess.aspx' 
OR url_site = 'https://sec2.paymentexpress.com/pxpay/pxpay/pxaccess.aspx';

UPDATE civicrm_payment_processor_type
SET url_site_default = 'https://sec.paymentexpress.com/pxpay/pxaccess.aspx',
    url_site_test_default = 'https://sec.paymentexpress.com/pxpay/pxaccess.aspx' 
WHERE name = 'Payment_Express';


-- CRM-8125
SELECT @option_group_id_languages := MAX(id) FROM civicrm_option_group WHERE name = 'languages';
SELECT @languages_max_weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_languages;

{if $multilingual}
   {foreach from=$locales item=locale}
     UPDATE civicrm_option_value SET label_{$locale} = '{ts escape="sql"}Persian (Iran){/ts}' WHERE value = 'fa' AND option_group_id = @option_group_id_languages;
   {/foreach}
{else}
     UPDATE civicrm_option_value SET label = '{ts escape="sql"}Persian (Iran){/ts}' WHERE value = 'fa' AND option_group_id = @option_group_id_languages;
{/if}

INSERT INTO civicrm_option_value
  (option_group_id, is_default, is_active, name, value, {localize field='label'}label{/localize}, weight)
VALUES
(@option_group_id_languages, 0, 1, 'de_CH', 'de', {localize}'{ts escape="sql"}German (Swiss){/ts}'{/localize}, @weight := @languages_max_weight + 1),
(@option_group_id_languages, 0, 1, 'es_PR', 'es', {localize}'{ts escape="sql"}Spanish; Castilian (Puerto Rico){/ts}'{/localize}, @weight := @languages_max_weight + 2);

-- CRM-8218, contact dashboard changes
{if $alterContactDashboard}
    ALTER TABLE `civicrm_dashboard` DROP `content`, DROP `created_date`;
    ALTER TABLE `civicrm_dashboard_contact`  ADD `content` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `weight`,  ADD `created_date` DATETIME NULL DEFAULT NULL AFTER `content`;
{/if}
