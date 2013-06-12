{include file='../CRM/Upgrade/4.3.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-10999
ALTER TABLE `civicrm_premiums` 
ADD COLUMN `premiums_nothankyou_position` int(10) unsigned DEFAULT '1';

-- CRM-11514 if contribution type name is null, assign it a name
UPDATE civicrm_contribution_type
SET name = CONCAT('Unknown_', id)
WHERE name IS NULL OR TRIM(name) = '';

-- CRM-8507
ALTER TABLE civicrm_custom_field
  ADD UNIQUE INDEX `UI_name_custom_group_id` (`name`, `custom_group_id`);

--CRM-10473 Added Missing Provinces of Ningxia Autonomous Region of China
INSERT INTO `civicrm_state_province`(`country_id`, `abbreviation`, `name`) VALUES
(1045, 'YN', 'Yinchuan'),
(1045, 'SZ', 'Shizuishan'),
(1045, 'WZ', 'Wuzhong'),
(1045, 'GY', 'Guyuan'),
(1045, 'ZW', 'Zhongwei');

-- CRM-10553
ALTER TABLE civicrm_contact
  ADD COLUMN `created_date` timestamp NULL DEFAULT NULL
  COMMENT 'When was the contact was created.';
ALTER TABLE civicrm_contact
  ADD COLUMN `modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  COMMENT 'When was the contact (or closely related entity) was created or modified or deleted.';

-- CRM-10296
DELETE FROM civicrm_job WHERE `api_action` = 'process_membership_reminder_date';
ALTER TABLE civicrm_membership 			DROP COLUMN reminder_date;
ALTER TABLE civicrm_membership_log 	DROP COLUMN renewal_reminder_date;
ALTER TABLE civicrm_membership_type
	DROP COLUMN renewal_reminder_day,
	DROP FOREIGN KEY FK_civicrm_membership_type_renewal_msg_id,
	DROP INDEX FK_civicrm_membership_type_renewal_msg_id,
	DROP COLUMN renewal_msg_id,
	DROP COLUMN autorenewal_msg_id;

-- CRM-10738
ALTER TABLE civicrm_msg_template
      CHANGE msg_text msg_text LONGTEXT NULL COMMENT 'Text formatted message',
      CHANGE msg_html msg_html LONGTEXT NULL COMMENT 'HTML formatted message';

-- CRM-10860
ALTER TABLE civicrm_contribution_page ADD COLUMN is_recur_installments tinyint(4) DEFAULT '0';
UPDATE civicrm_contribution_page SET is_recur_installments='1';

-- CRM-10863
SELECT @country_id := id from civicrm_country where name = 'Luxembourg' AND iso_code = 'LU';
INSERT IGNORE INTO `civicrm_state_province`(`country_id`, `abbreviation`, `name`) VALUES
(@country_id, 'L', 'Luxembourg');

-- CRM-10899 and CRM-10999
{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_group SET title_{$locale} = '{ts escape="sql"}Currencies Enabled{/ts}' WHERE name = "currencies_enabled";
    ALTER TABLE `civicrm_premiums` 
      ADD COLUMN premiums_nothankyou_label_{$locale} varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label displayed for No Thank-you option in premiums block (e.g. No thank you)';
  {/foreach}
{else}
    UPDATE civicrm_option_group SET title = '{ts escape="sql"}Currencies Enabled{/ts}' WHERE name = "currencies_enabled";
{/if}

-- CRM-11047
ALTER TABLE civicrm_job DROP COLUMN api_prefix;

-- CRM-11068, CRM-10678, CRM-11759
ALTER TABLE civicrm_group
  ADD refresh_date datetime default NULL COMMENT 'Date and time when we need to refresh the cache next.' AFTER `cache_date`,
  ADD COLUMN `created_id` INT(10) unsigned DEFAULT NULL COMMENT 'FK to contact table, creator of the group.';

-- CRM-11759
ALTER TABLE civicrm_group
  ADD CONSTRAINT `FK_civicrm_group_created_id` FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL;

INSERT INTO `civicrm_job`
    ( domain_id, run_frequency, last_run, name, description, api_entity, api_action, parameters, is_active )
VALUES
    ( {$domainID}, 'Always' , NULL, '{ts escape="sql" skip="true"}Rebuild Smart Group Cache{/ts}', '{ts escape="sql" skip="true"}Rebuilds the smart group cache.{/ts}', 'job', 'group_rebuild', '{ts escape="sql" skip="true"}limit=Number optional-Limit the number of smart groups rebuild{/ts}', 0),
    ( {$domainID}, 'Daily' , NULL, '{ts escape="sql" skip="true"}Validate Email Address from Mailings.{/ts}', '{ts escape="sql" skip="true"}Updates the reset_date on an email address to indicate that there was a valid delivery to this email address.{/ts}', 'mailing', 'update_email_resetdate', '{ts escape="sql" skip="true"}minDays, maxDays=Consider mailings that have completed between minDays and maxDays{/ts}', 0);

-- CRM-11117
INSERT IGNORE INTO `civicrm_setting` (`group_name`, `name`, `value`, `domain_id`, `is_domain`) VALUES ('CiviCRM Preferences', 'activity_assignee_notification_ics', 's:1:"0";', {$domainID}, '1');

-- CRM-10885
ALTER TABLE civicrm_dedupe_rule_group
  ADD used enum('Unsupervised','Supervised','General') COLLATE utf8_unicode_ci NOT NULL COMMENT 'Whether the rule should be used for cases where usage is Unsupervised, Supervised OR General(programatically)' AFTER threshold;

UPDATE civicrm_dedupe_rule_group
  SET used = 'General' WHERE is_default = 0;

UPDATE civicrm_dedupe_rule_group
    SET used = CASE level
        WHEN 'Fuzzy' THEN 'Supervised'
        WHEN 'Strict'   THEN 'Unsupervised'
    END
WHERE is_default = 1;

UPDATE civicrm_dedupe_rule_group
  SET name = CONCAT_WS('', `contact_type`, `used`)
WHERE is_default = 1 OR is_reserved = 1;

UPDATE civicrm_dedupe_rule_group
  SET  title = 'Name and Email'
WHERE contact_type IN ('Organization', 'Household') AND used IN ('Unsupervised', 'Supervised');

UPDATE civicrm_dedupe_rule_group
    SET title = CASE used
        WHEN 'Supervised' THEN 'Name and Email (reserved)'
        WHEN 'Unsupervised'   THEN 'Email (reserved)'
         WHEN 'General' THEN 'Name and Address (reserved)'
    END
WHERE contact_type = 'Individual' AND is_reserved = 1;

ALTER TABLE civicrm_dedupe_rule_group DROP COLUMN level;

-- CRM-10771
ALTER TABLE civicrm_uf_field
  ADD `is_multi_summary` tinyint(4) DEFAULT '0' COMMENT 'Include in multi-record listing?';

-- CRM-1115
-- note that country names are not translated in the DB
SELECT @region_id   := max(id) from civicrm_worldregion where name = "Europe and Central Asia";
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Kosovo", "XK", @region_id, 0);

UPDATE civicrm_country SET name = 'Libya' WHERE name LIKE 'Libyan%';
UPDATE civicrm_country SET name = 'Congo, Republic of the' WHERE name = 'Congo';

-- CRM-10621 Add component report links to reports menu for upgrade
SELECT @reportlastID       := MAX(id) FROM civicrm_navigation where name = 'Reports' AND domain_id = {$domainID};
SELECT @max_weight     := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=99&reset=1', '{ts escape="sql" skip="true"}Contact Reports{/ts}', 'Contact Reports', 'administer CiviCRM', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1) );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=2&reset=1', '{ts escape="sql" skip="true"}Contribution Reports{/ts}', 'Contribution Reports', 'access CiviContribute', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1) );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=6&reset=1', '{ts escape="sql" skip="true"}Pledge Reports{/ts}', 'Pledge Reports', 'access CiviPledge', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1) );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=1&reset=1', '{ts escape="sql" skip="true"}Event Reports{/ts}', 'Event Reports', 'access CiviEvent', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1));
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=4&reset=1', '{ts escape="sql" skip="true"}Mailing Reports{/ts}', 'Mailing Reports', 'access CiviMail', '', @reportlastID, '1', 0,   (SELECT @max_weight := @max_weight+1));
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=3&reset=1', '{ts escape="sql" skip="true"}Membership Reports{/ts}', 'Membership Reports', 'access CiviMember', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1));
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=9&reset=1', '{ts escape="sql" skip="true"}Campaign Reports{/ts}', 'Campaign Reports', 'interview campaign contacts,release campaign contacts,reserve campaign contacts,manage campaign,administer CiviCampaign,gotv campaign contacts', 'OR', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1));
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=7&reset=1', '{ts escape="sql" skip="true"}Case Reports{/ts}', 'Case Reports', 'access my cases and activities,access all cases and activities,administer CiviCase', 'OR', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1) );
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/report/list&compid=5&reset=1', '{ts escape="sql" skip="true"}Grant Reports{/ts}', 'Grant Reports', 'access CiviGrant', '', @reportlastID, '1', 0, (SELECT @max_weight := @max_weight+1) );

-- CRM-11148 Multiple terms membership signup and renewal via price set
ALTER TABLE `civicrm_price_field_value` ADD COLUMN `membership_num_terms` INT(10) NULL DEFAULT NULL COMMENT 'Maximum number of related memberships.' AFTER `membership_type_id`;

-- CRM-11070
SELECT @option_group_id_tuf := max(id) from civicrm_option_group where name = 'tag_used_for';
SELECT @weight              := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_tuf;

INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`)
VALUES
(@option_group_id_tuf, {localize}'Attachments'{/localize}, 'civicrm_file', 'Attachments', NULL, 0, 0, @weight = @weight + 1);

ALTER TABLE civicrm_extension MODIFY COLUMN type ENUM( 'payment', 'search', 'report', 'module','sms') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;

-- CRM-9914
SELECT @option_group_id_sms_provider_name := max(id) from civicrm_option_group where name = 'sms_provider_name';
DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_sms_provider_name AND name = 'Clickatell';

-- CRM-11292
ALTER TABLE `civicrm_phone`
ADD `phone_numeric` varchar(32)
COMMENT 'Phone number stripped of all whitespace, letters, and punctuation.'
AFTER `phone_ext`,
ADD INDEX phone_numeric_index(`phone_numeric`);


-- civiaccounts upgrade

-- ADD fields w.r.t 10.6 mwb
ALTER TABLE `civicrm_financial_account`
CHANGE `account_type_id` financial_account_type_id int(10) unsigned NOT NULL DEFAULT '3' COMMENT 'Version identifier of financial_type',
ADD `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Financial Type Description.',
ADD `parent_id` int(10) unsigned DEFAULT NULL COMMENT 'Parent ID in account hierarchy',
ADD `is_header_account` tinyint(4) DEFAULT NULL COMMENT 'Is this a header account which does not allow transactions to be posted against it directly, but only to its sub-accounts?',
ADD `accounting_code` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Optional value for mapping monies owed and received to accounting system codes.',
ADD `account_type_code` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Optional value for mapping account types to accounting system account categories (QuickBooks Account Type Codes for example).',
ADD `is_deductible` tinyint(4) DEFAULT '1' COMMENT 'Is this account tax-deductible?',
ADD `is_tax` tinyint(4) DEFAULT '0' COMMENT 'Is this account for taxes?',
ADD `tax_rate` decimal(9,8) DEFAULT '0.00' COMMENT 'The percentage of the total_amount that is due for this tax.',
ADD `is_reserved` tinyint(4) DEFAULT NULL COMMENT 'Is this a predefined system object?',
ADD `is_active` tinyint(4) DEFAULT NULL COMMENT 'Is this property active?',
ADD `is_default` tinyint(4) DEFAULT NULL COMMENT 'Is this account the default one (or default tax one) for its financial_account_type?',
ADD CONSTRAINT `UI_name` UNIQUE INDEX (`name`),
ADD CONSTRAINT `FK_civicrm_financial_account_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `civicrm_financial_account`(id);

-- CRM-8425
-- Rename table civicrm_contribution_type to civicrm_financial_type
RENAME TABLE `civicrm_contribution_type` TO `civicrm_financial_type`;

ALTER TABLE `civicrm_financial_type`
CHANGE `name` `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Financial Type Name.',
ADD CONSTRAINT `UI_id` UNIQUE INDEX(id),
DROP INDEX UI_name;

CREATE TABLE IF NOT EXISTS `civicrm_entity_financial_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Links to an entity_table like civicrm_financial_type',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Links to an id in the entity_table, such as vid in civicrm_financial_type',
  `account_relationship` int(10) unsigned NOT NULL COMMENT 'FK to a new civicrm_option_value (account_relationship)',
  `financial_account_id` int(10) unsigned NOT NULL COMMENT 'FK to the financial_account_id',
  PRIMARY KEY (`id`),
KEY `FK_civicrm_entity_financial_account_financial_account_id` (`financial_account_id`)
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Constraints for table `civicrm_entity_financial_account`
 ALTER TABLE `civicrm_entity_financial_account`
  ADD CONSTRAINT `FK_civicrm_entity_financial_account_financial_account_id` FOREIGN KEY (`financial_account_id`) REFERENCES `civicrm_financial_account` (`id`);

-- CRM-9730 Table structure for table `civicrm_financial_item`
--

CREATE TABLE IF NOT EXISTS `civicrm_financial_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time the item was created',
  `transaction_date` datetime NOT NULL COMMENT 'Date and time of the source transaction',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'FK to Contact ID of contact the item is from',
  `description` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Human readable description of this item, to ease display without lookup of source item.',
  `amount` decimal(20,2) NOT NULL DEFAULT '0.00' COMMENT 'Total amount of this item',
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Currency for the amount',
  `financial_account_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_financial_account',
  `status_id` int(10) unsigned DEFAULT NULL COMMENT 'Payment status: test, paid, part_paid, unpaid (if empty assume unpaid)',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'The table providing the source of this item such as civicrm_line_item',
  `entity_id` int(10) unsigned DEFAULT NULL COMMENT 'The specific source item that is responsible for the creation of this financial_item',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UI_id` (`id`),
  KEY `IX_created_date` (`created_date`),
  KEY `IX_transaction_date` (`transaction_date`),
  KEY `IX_entity` (`entity_table`,`entity_id`),
  KEY `FK_civicrm_financial_item_contact_id` (`contact_id`),
  KEY `FK_civicrm_financial_item_financial_account_id` (`financial_account_id`)
)ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `civicrm_batch`
ADD `payment_instrument_id` int(10) unsigned DEFAULT NULL COMMENT 'fk to Payment Instrument options in civicrm_option_values',
ADD `exported_date` datetime DEFAULT NULL;

ALTER TABLE `civicrm_financial_item`
  ADD CONSTRAINT `FK_civicrm_financial_item_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`),
  ADD CONSTRAINT `FK_civicrm_financial_item_financial_account_id` FOREIGN KEY (`financial_account_id`) REFERENCES `civicrm_financial_account` (`id`);

ALTER TABLE `civicrm_entity_financial_trxn`
DROP currency;

-- CRM-12312
UPDATE civicrm_event SET contribution_type_id = NULL WHERE contribution_type_id = 0;

-- CRM-9189 and CRM-8425 change fk's to financial_account.id in our branch that will need to be changed to an fk to financial_type.id

ALTER TABLE `civicrm_pledge`
DROP FOREIGN KEY FK_civicrm_pledge_contribution_type_id,
DROP INDEX FK_civicrm_pledge_contribution_type_id;

ALTER TABLE `civicrm_pledge`
CHANGE `contribution_type_id` `financial_type_id` int unsigned COMMENT 'FK to Financial Type';

ALTER TABLE `civicrm_pledge`
ADD CONSTRAINT FK_civicrm_pledge_financial_type_id  FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

ALTER TABLE `civicrm_membership_type`
DROP FOREIGN KEY FK_civicrm_membership_type_contribution_type_id,
DROP INDEX FK_civicrm_membership_type_contribution_type_id;

ALTER TABLE `civicrm_membership_type`
CHANGE `contribution_type_id` `financial_type_id` int unsigned NOT NULL COMMENT 'If membership is paid by a contribution - what financial type should be used. FK to civicrm_financial_type.id';

ALTER TABLE `civicrm_membership_type`
ADD CONSTRAINT FK_civicrm_membership_type_financial_type_id  FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

ALTER TABLE `civicrm_price_set`
DROP FOREIGN KEY FK_civicrm_price_set_contribution_type_id,
DROP INDEX FK_civicrm_price_set_contribution_type_id;

ALTER TABLE `civicrm_price_set`
CHANGE `contribution_type_id` `financial_type_id` int unsigned COMMENT 'If membership is paid by a contribution - what financial type should be used. FK to civicrm_financial_type.id';

ALTER TABLE `civicrm_price_set`
ADD CONSTRAINT FK_civicrm_price_set_financial_type_id  FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

ALTER TABLE `civicrm_event`
CHANGE `contribution_type_id` `financial_type_id` int unsigned COMMENT 'Financial type assigned to paid event registrations for this event. Required if is_monetary is true.';

ALTER TABLE `civicrm_contribution`
DROP FOREIGN KEY FK_civicrm_contribution_contribution_type_id,
DROP INDEX FK_civicrm_contribution_contribution_type_id;

ALTER TABLE `civicrm_contribution`
CHANGE `contribution_type_id` `financial_type_id` int unsigned COMMENT 'FK to Financial Type for (total_amount - non_deductible_amount).';

ALTER TABLE `civicrm_contribution`
ADD CONSTRAINT FK_civicrm_contribution_financial_type_id FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

ALTER TABLE `civicrm_contribution_page`
DROP FOREIGN KEY FK_civicrm_contribution_page_contribution_type_id,
DROP INDEX FK_civicrm_contribution_page_contribution_type_id;

ALTER TABLE `civicrm_contribution_page`
CHANGE `contribution_type_id` `financial_type_id` int unsigned DEFAULT NULL COMMENT 'default financial type assigned to contributions submitted via this page, e.g. Contribution, Campaign Contribution',
ADD `is_partial_payment` tinyint(4) DEFAULT '0' COMMENT 'is partial payment enabled for this event',
ADD `min_initial_amount` decimal(20,2) DEFAULT NULL COMMENT 'Minimum initial amount for partial payment';

{if $multilingual}
  {foreach from=$locales item=loc}
    ALTER TABLE `civicrm_contribution_page`
      ADD `initial_amount_label_{$loc}` varchar(255) COLLATE utf8_unicode_ci COMMENT 'Initial amount label for partial payment',
      ADD `initial_amount_help_text_{$loc}` text COLLATE utf8_unicode_ci COMMENT 'Initial amount help text for partial payment';
  {/foreach}
{else}
  ALTER TABLE `civicrm_contribution_page`
    ADD `initial_amount_label` varchar(255) COLLATE utf8_unicode_ci COMMENT 'Initial amount label for partial payment',
    ADD `initial_amount_help_text` text COLLATE utf8_unicode_ci COMMENT 'Initial amount help text for partial payment';
{/if}

ALTER TABLE `civicrm_contribution_page`
ADD CONSTRAINT  FK_civicrm_contribution_page_financial_type_id FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

ALTER TABLE `civicrm_contribution_recur`
CHANGE `contribution_type_id` `financial_type_id` int unsigned COMMENT 'FK to Financial Type';

ALTER TABLE `civicrm_contribution_recur`
ADD CONSTRAINT FK_civicrm_contribution_recur_financial_type_id FOREIGN KEY (`financial_type_id`) REFERENCES civicrm_financial_type (id);

-- CRM-9083
ALTER TABLE `civicrm_financial_trxn` CHANGE `to_account_id` `to_financial_account_id` int unsigned COMMENT 'FK to financial_financial_account table.',
CHANGE `from_account_id` `from_financial_account_id` int unsigned COMMENT 'FK to financial_account table.',
ADD `status_id` int(10) unsigned DEFAULT NULL,
CHANGE `trxn_id` trxn_id varchar(255) COMMENT 'unique processor transaction id, bank id + trans id,... depending on payment_method',
CHANGE `trxn_date` trxn_date datetime DEFAULT NULL,
ADD `payment_instrument_id` int unsigned DEFAULT NULL COMMENT 'FK to payment_instrument option group values',
ADD `check_number` VARCHAR( 255 ) NULL DEFAULT NULL,
ADD INDEX `UI_ftrxn_check_number` (`check_number`),
ADD INDEX `UI_ftrxn_payment_instrument_id` (`payment_instrument_id`);

ALTER TABLE `civicrm_financial_trxn`
ADD CONSTRAINT FK_civicrm_financial_trxn_to_financial_account_id FOREIGN KEY (`to_financial_account_id`) REFERENCES civicrm_financial_account (id),
ADD CONSTRAINT FK_civicrm_financial_trxn_from_financial_account_id FOREIGN KEY (`from_financial_account_id`) REFERENCES civicrm_financial_account (id);

ALTER TABLE `civicrm_financial_trxn` ADD `payment_processor_id` int unsigned COMMENT 'Payment Processor for this contribution Page';

-- Fill in the payment_processor_id based on a lookup using the payment_processor field
UPDATE `civicrm_payment_processor` cppt,  `civicrm_financial_trxn` cft
SET cft.`payment_processor_id` = cppt.`id`
WHERE cft.`payment_processor` = cppt.`payment_processor_type` and `is_test` = 0;

-- remove payment_processor field
ALTER TABLE `civicrm_financial_trxn` DROP `payment_processor`;

ALTER TABLE `civicrm_financial_trxn`
  ADD CONSTRAINT `FK_civicrm_financial_trxn_payment_processor_id` FOREIGN KEY (`payment_processor_id`) REFERENCES `civicrm_payment_processor` (`id`) ON DELETE SET NULL;

-- Drop index for civicrm_financial_trxn.trxn_id and set default to null
ALTER TABLE `civicrm_financial_trxn` CHANGE `trxn_id` `trxn_id` varchar( 255 ) DEFAULT NULL ;
ALTER TABLE `civicrm_financial_trxn` DROP INDEX UI_ft_trxn_id;

-- remove trxn_type field
ALTER TABLE `civicrm_financial_trxn` DROP `trxn_type`;

-- CRM-9731

ALTER TABLE `civicrm_payment_processor` ADD `payment_processor_type_id` int(10) unsigned NULL AFTER `description`,
ADD CONSTRAINT `FK_civicrm_payment_processor_payment_processor_type_id` FOREIGN KEY (`payment_processor_type_id`) REFERENCES `civicrm_payment_processor_type` (`id`);

UPDATE `civicrm_payment_processor` , `civicrm_payment_processor_type`
SET payment_processor_type_id = `civicrm_payment_processor_type`.id
WHERE payment_processor_type = `civicrm_payment_processor_type`.name;

ALTER TABLE `civicrm_payment_processor` DROP `payment_processor_type`;

-- CRM-9730
ALTER TABLE `civicrm_price_field_value` ADD `deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT '0.00' COMMENT 'Tax-deductible portion of the amount';

ALTER TABLE `civicrm_line_item` ADD `deductible_amount` DECIMAL( 20, 2 ) NOT NULL DEFAULT '0.00' COMMENT 'Tax-deductible portion of the amount';

ALTER TABLE `civicrm_price_field_value` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
 ADD CONSTRAINT `FK_civicrm_price_field_value_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

ALTER TABLE `civicrm_line_item` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
 ADD CONSTRAINT `FK_civicrm_line_item_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

ALTER TABLE `civicrm_grant` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
 ADD CONSTRAINT `FK_civicrm_grant_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

ALTER TABLE `civicrm_product` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
ADD CONSTRAINT `FK_civicrm_product_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

ALTER TABLE `civicrm_premiums_product` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
ADD CONSTRAINT `FK_civicrm_premiums_product_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

ALTER TABLE `civicrm_contribution_product` ADD
`financial_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Financial Type.',
ADD CONSTRAINT `FK_civicrm_contribution_product_financial_type_id` FOREIGN KEY (`financial_type_id`) REFERENCES `civicrm_financial_type` (`id`);

-- CRM-11122
ALTER TABLE `civicrm_discount`
DROP FOREIGN KEY FK_civicrm_discount_option_group_id,
DROP INDEX FK_civicrm_discount_option_group_id;

ALTER TABLE `civicrm_discount` CHANGE `option_group_id` `price_set_id` INT( 10 ) UNSIGNED NOT NULL COMMENT 'FK to civicrm_price_set';

ALTER TABLE `civicrm_discount`
  ADD CONSTRAINT `FK_civicrm_discount_price_set_id` FOREIGN KEY (`price_set_id`) REFERENCES `civicrm_price_set` (`id`) ON DELETE CASCADE;

-- CRM-8425

UPDATE civicrm_navigation SET  `label` = 'Financial Types', `name` = 'Financial Types', `url` = 'civicrm/admin/financial/financialType?reset=1' WHERE `name` = 'Contribution Types';

-- CRM-9199
-- Insert menu item at Administer > CiviContribute, below the section break below Premiums (Thank-you Gifts), just below Financial Account.

SELECT @parent_id := id from `civicrm_navigation` where name = 'CiviContribute' AND domain_id = {$domainID};
SELECT @add_weight_id := weight from `civicrm_navigation` where `name` = 'Financial Types' and `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` > @add_weight_id;

INSERT INTO `civicrm_navigation`
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
	( {$domainID}, 'civicrm/admin/financial/financialAccount&reset=1',      '{ts escape="sql" skip="true"}Financial Account{/ts}', 'Financial Account', 'access CiviContribute,administer CiviCRM', 'AND', @parent_id, '1', NULL, @add_weight_id + 1 );

-- CRM-10944
SELECT @contributionlastID := max(id) from civicrm_navigation where name = 'Contributions' AND domain_id = {$domainID};

SELECT @pledgeWeight := weight from civicrm_navigation where name = 'Pledges' and parent_id = @contributionlastID;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @contributionlastID
AND `weight` > @pledgeWeight;

INSERT INTO civicrm_navigation
    (domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight)
VALUES
    ({$domainID}, NULL, '{ts escape="sql" skip="true"}Accounting Batches{/ts}',  'Accounting Batches', 'access CiviContribute', '', @contributionlastID, '1',  1,   @pledgeWeight+1);
SET @financialTransactionID:=LAST_INSERT_ID();

INSERT INTO civicrm_navigation
    (domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ({$domainID}, 'civicrm/financial/batch&reset=1&action=add',                             '{ts escape="sql" skip="true"}New Batch{/ts}',          'New Batch',         'access CiviContribute', 'AND',  @financialTransactionID, '1', NULL, 1),
    ({$domainID}, 'civicrm/financial/financialbatches?reset=1&batchStatus=1', '{ts escape="sql" skip="true"}Open Batches{/ts}',          'Open Batches',         'access CiviContribute', 'AND',  @financialTransactionID, '1', NULL, 2),
    ({$domainID}, 'civicrm/financial/financialbatches?reset=1&batchStatus=2', '{ts escape="sql" skip="true"}Closed Batches{/ts}',          'Closed Batches',         'access CiviContribute', 'AND',  @financialTransactionID, '1', NULL, 3),
    ({$domainID}, 'civicrm/financial/financialbatches?reset=1&batchStatus=5', '{ts escape="sql" skip="true"}Exported Batches{/ts}',          'Exported Batches',         'access CiviContribute', 'AND',  @financialTransactionID, '1', NULL, 4);

-- Insert an entry for financial_account_type in civicrm_option_group and for the the following financial account types in civicrm_option_value as per CRM-8425
INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}title{/localize}, `is_reserved`, `is_active`)
VALUES
   ('financial_account_type', {localize}'{ts escape="sql"}Financial Account Type{/ts}'{/localize}, 1, 1),
   ('account_relationship', {localize}'{ts escape="sql"}Account Relationship{/ts}'{/localize}, 1, 1),
   ('financial_item_status', {localize}'{ts escape="sql"}Financial Item Status{/ts}'{/localize}, 1, 1),
   ('batch_mode', {localize}'{ts escape="sql"}Batch Mode{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_fat := max(id) from civicrm_option_group where name = 'financial_account_type';
SELECT @option_group_id_arel           := max(id) from civicrm_option_group where name = 'account_relationship';
SELECT @option_group_id_financial_item_status := max(id) from civicrm_option_group where name = 'financial_item_status';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_fat, {localize}'{ts escape="sql"}Asset{/ts}'{/localize}, 1, 'Asset', NULL, 0, 0, 1, {localize}'Things you own'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_fat, {localize}'{ts escape="sql"}Liability{/ts}'{/localize}, 2, 'Liability', NULL, 0, 0, 2, {localize}'Things you own, like a grant still to be disbursed'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_fat, {localize}'{ts escape="sql"}Revenue{/ts}'{/localize}, 3, 'Revenue', NULL, 0, 1, 3, {localize}'Income from contributions and sales of tickets and memberships'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_fat, {localize}'{ts escape="sql"}Cost of Sales{/ts}'{/localize}, 4, 'Cost of Sales', NULL, 0, 0, 4, {localize}'Costs incurred to get revenue, e.g. premiums for donations, dinner for a fundraising dinner ticket'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_fat, {localize}'{ts escape="sql"}Expenses{/ts}'{/localize}, 5, 'Expenses', NULL, 0, 0, 5, {localize}'Things that are paid for that are consumable, e.g. grants disbursed'{/localize}, 0, 1, 1, 2, NULL),

-- Financial account relationship
   (@option_group_id_arel, {localize}'{ts escape="sql"}Income Account is{/ts}'{/localize}, 1, 'Income Account is', NULL, 0, 1, 1, {localize}'Income Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Credit/Contra Account is{/ts}'{/localize}, 2, 'Credit/Contra Account is', NULL, 0, 0, 2, {localize}'Credit/Contra Account is'{/localize}, 0, 1, 0, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Accounts Receivable Account is{/ts}'{/localize}, 3, 'Accounts Receivable Account is', NULL, 0, 0, 3, {localize}'Accounts Receivable Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Credit Liability Account is{/ts}'{/localize}, 4, 'Credit Liability Account is', NULL, 0, 0, 4, {localize}'Credit Liability Account is'{/localize}, 0, 1, 0, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Expense Account is{/ts}'{/localize}, 5, 'Expense Account is', NULL, 0, 0, 5, {localize}'Expense Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Asset Account is{/ts}'{/localize}, 6, 'Asset Account is', NULL, 0, 0, 6, {localize}'Asset Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Cost of Sales Account is{/ts}'{/localize}, 7, 'Cost of Sales Account is', NULL, 0, 0, 7, {localize}'Cost of Sales Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Premiums Inventory Account is{/ts}'{/localize}, 8, 'Premiums Inventory Account is', NULL, 0, 0, 8, {localize}'Premiums Inventory Account is'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_arel, {localize}'{ts escape="sql"}Discounts Account is{/ts}'{/localize}, 9, 'Discounts Account is', NULL, 0, 0, 9, {localize}'Discounts Account is'{/localize}, 0, 1, 1, 2, NULL),

-- Financial Item Status
   (@option_group_id_financial_item_status, {localize}'{ts escape="sql"}Paid{/ts}'{/localize}, 1, 'Paid', NULL, 0, 0, 1, {localize}'Paid'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_financial_item_status, {localize}'{ts escape="sql"}Partially paid{/ts}'{/localize}, 2, 'Partially paid', NULL, 0, 0, 2, {localize}'Partially paid'{/localize}, 0, 1, 1, 2, NULL),
   (@option_group_id_financial_item_status, {localize}'{ts escape="sql"}Unpaid{/ts}'{/localize}, 3, 'Unpaid', NULL, 0, 0, 1, {localize}'Unpaid'{/localize}, 0, 1, 1, 2, NULL);

-- Data migration from civicrm_contibution_type to civicrm_financial_account, civicrm_financial_type, civicrm_entity_financial_account
SELECT @opval := value FROM civicrm_option_value WHERE name = 'Revenue' and option_group_id = @option_group_id_fat;
SELECT @domainContactId := contact_id from civicrm_domain where id = {$domainID};

INSERT INTO `civicrm_financial_account`
  (`id`, `name`, `description`, `is_deductible`, `is_reserved`, `is_active`, `financial_account_type_id`, `contact_id`, accounting_code)
  SELECT id, name, CONCAT('Default account for ', name), is_deductible, is_reserved, is_active, @opval, @domainContactId, accounting_code
  FROM `civicrm_financial_type`;

-- CRM-9306 and CRM-11657
UPDATE `civicrm_financial_account` SET `is_default` = 0, `account_type_code` = 'INC';

SELECT @option_value_rel_id  := value FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_arel AND `name` = 'Income Account is';
SELECT @opexp := value FROM civicrm_option_value WHERE name = 'Expenses' and option_group_id = @option_group_id_fat;
SELECT @opAsset := value FROM civicrm_option_value WHERE name = 'Asset' and option_group_id = @option_group_id_fat;
SELECT @opLiability := value FROM civicrm_option_value WHERE name = 'Liability' and option_group_id = @option_group_id_fat;
SELECT @opCost := value FROM civicrm_option_value WHERE name = 'Cost of Sales' and option_group_id = @option_group_id_fat;

-- CRM-11522 drop accounting_code after coping its values into financial_account
ALTER TABLE civicrm_financial_type DROP accounting_code;

INSERT INTO
   `civicrm_financial_account` (`name`, `contact_id`, `financial_account_type_id`, `description`, `accounting_code`, `account_type_code`, `is_reserved`, `is_active`, `is_deductible`, `is_default`)
VALUES
  ('{ts escape="sql"}Banking Fees{/ts}'         , @domainContactId, @opexp, 'Payment processor fees and manually recorded banking fees', '5200', 'EXP', 0, 1, 0, 0),
  ('{ts escape="sql"}Deposit Bank Account{/ts}' , @domainContactId, @opAsset, 'All manually recorded cash and cheques go to this account', '1100', 'BANK', 0, 1, 0, 1),
  ('{ts escape="sql"}Accounts Receivable{/ts}'  , @domainContactId, @opAsset, 'Amounts to be received later (eg pay later event revenues)', '1200', 'AR', 0, 1, 0, 0),
  ('{ts escape="sql"}Accounts Payable{/ts}'     , @domainContactId, @opLiability, 'Amounts to be paid out such as grants and refunds', '2200', 'AP', 0, 1, 0, 0),
  ('{ts escape="sql"}Premiums{/ts}'             , @domainContactId, @opCost, 'Account to record cost of premiums provided to payors', '5100', 'COGS', 0, 1, 0, 0),
  ('{ts escape="sql"}Premiums Inventory{/ts}'   , @domainContactId, @opAsset, 'Account representing value of premiums inventory', '1375', 'OCASSET', 0, 1, 0, 0),
  ('{ts escape="sql"}Discounts{/ts}'            , @domainContactId, @opval, 'Contra-revenue account for amounts discounted from sales', '4900', 'INC', 0, 1, 0, 0),
  ('{ts escape="sql"}Payment Processor Account{/ts}', @domainContactId, @opAsset, 'Account to record payments into a payment processor merchant account', '1150', 'BANK', 0, 1, 0, 0);

-- CRM-10926
SELECT @option_value_rel_id_exp  := value FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_arel AND `name` = 'Expense Account is';
SELECT @option_value_rel_id_ar  := value FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_arel AND `name` = 'Accounts Receivable Account is';
SELECT @option_value_rel_id_as  := value FROM `civicrm_option_value` WHERE `option_group_id` = @option_group_id_arel AND `name` = 'Asset Account is';

SELECT @financial_account_id_bf	       := max(id) FROM `civicrm_financial_account` WHERE `name` = 'Banking Fees';
SELECT @financial_account_id_ap	       := max(id) FROM `civicrm_financial_account` WHERE `name` = 'Accounts Receivable';

INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
SELECT 'civicrm_financial_type', ft.id, @option_value_rel_id, fa.id
FROM `civicrm_financial_type` as ft LEFT JOIN `civicrm_financial_account` as fa ON ft.id = fa.id;

-- Banking Fees
INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
SELECT 'civicrm_financial_type', ft.id, @option_value_rel_id_exp,  @financial_account_id_bf
FROM `civicrm_financial_type` as ft;

-- Accounts Receivable
INSERT INTO `civicrm_entity_financial_account`
     ( entity_table, entity_id, account_relationship, financial_account_id )
SELECT 'civicrm_financial_type', ft.id, @option_value_rel_id_ar, @financial_account_id_ap
FROM `civicrm_financial_type` as ft;

-- CRM-11516
SELECT @financial_account_id_ar := max(id) FROM `civicrm_financial_account` WHERE `name` = 'Deposit Bank Account';
SELECT @financial_account_id_pp := max(id) FROM `civicrm_financial_account` WHERE `name` = 'Payment Processor Account';

INSERT INTO  civicrm_entity_financial_account (entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_option_value', cov.id, @option_value_rel_id_as, @financial_account_id_ar  FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cog.id = cov.option_group_id
WHERE cog.name = 'payment_instrument' AND cov.name NOT IN ('Credit Card', 'Debit Card');

INSERT INTO  civicrm_entity_financial_account (entity_table, entity_id, account_relationship, financial_account_id)
SELECT 'civicrm_option_value', cov.id, @option_value_rel_id_as, @financial_account_id_pp  FROM `civicrm_option_group` cog
LEFT JOIN civicrm_option_value cov ON cog.id = cov.option_group_id
WHERE cog.name = 'payment_instrument' AND cov.name IN ('Credit Card', 'Debit Card');


-- CRM-11515
SELECT @financial_account_id_ppa := max(id) FROM `civicrm_financial_account` WHERE `name` = 'Payment Processor Account';

INSERT INTO civicrm_entity_financial_account (`entity_table`, `entity_id`, `account_relationship`, `financial_account_id`)
SELECT 'civicrm_payment_processor', id, @option_value_rel_id_as, @financial_account_id_ppa FROM `civicrm_payment_processor`;

-- CRM-9923 and CRM-11037
SELECT @option_group_id_batch_status   := max(id) from civicrm_option_group where name = 'batch_status';

SELECT @weight                 := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`)
VALUES
   (@option_group_id_batch_status, {localize}'Data Entry'{/localize}, @weight = @weight + 1, 'Data Entry', NULL, 0, 0, @weight = @weight + 1),
   (@option_group_id_batch_status, {localize}'Reopened'{/localize}, @weight = @weight + 1, 'Reopened', NULL, 0, 0, @weight = @weight + 1),
   (@option_group_id_batch_status, {localize}'Exported'{/localize}, @weight = @weight + 1, 'Exported' , NULL, 0, 0, @weight = @weight + 1);

-- Insert Batch Modes.

SELECT @option_group_id_batch_modes   := max(id) from civicrm_option_group where name = 'batch_mode';
SELECT @weight := MAX(value) FROM civicrm_option_value WHERE option_group_id = @option_group_id_batch_status;
INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`)
VALUES
   (@option_group_id_batch_modes, {localize}'Manual Batch'{/localize}, @weight = @weight + 1, 'Manual Batch', NULL, 0, 0, @weight = @weight + 1),
   (@option_group_id_batch_modes, {localize}'Automatic Batch'{/localize}, @weight = @weight + 1, 'Automatic Batch' , NULL, 0, 0, @weight = @weight + 1);

-- End of civiaccounts upgrade

-- CRM-10933
ALTER TABLE `civicrm_report_instance`
ADD COLUMN  `drilldown_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to instance ID drilldown to',
ADD CONSTRAINT `FK_civicrm_report_instance_drilldown_id` FOREIGN KEY (`drilldown_id`) REFERENCES `civicrm_report_instance` (`id`) ON DELETE SET NULL;

-- CRM-10012
ALTER TABLE `civicrm_membership_type`
ADD COLUMN `max_related` INT(10) unsigned DEFAULT NULL COMMENT 'Maximum number of related memberships.' AFTER `relationship_direction`;
ALTER TABLE `civicrm_membership`
ADD COLUMN `max_related` INT(10) unsigned DEFAULT NULL COMMENT 'Maximum number of related memberships (membership_type override).' AFTER `owner_membership_id`;
ALTER TABLE `civicrm_membership_log`
ADD COLUMN `max_related` INT(10) unsigned DEFAULT NULL COMMENT 'Maximum number of related memberships.' AFTER `membership_type_id`;

-- CRM-11358
INSERT INTO `civicrm_dashboard`
(`domain_id`, {localize field='label'}`label`{/localize}, `url`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `is_active`, `weight`, `fullscreen_url`, `is_fullscreen`, `is_reserved`)
SELECT id, {localize}'{ts escape="sql"}CiviCRM News{/ts}'{/localize}, 'civicrm/dashlet/blog&reset=1&snippet=5', 'access CiviCRM', NULL, 0, 0, 1, 0, 'civicrm/dashlet/blog&reset=1&snippet=5&context=dashletFullscreen', 1, 1
FROM `civicrm_domain`;

INSERT INTO `civicrm_dashboard_contact` (dashboard_id, contact_id, column_no, is_active)
SELECT (SELECT MAX(id) FROM `civicrm_dashboard`), contact_id, 1, IF (SUM(is_active) > 0, 0, 1)
FROM `civicrm_dashboard_contact` WHERE 1 GROUP BY contact_id;

-- CRM-11387
ALTER TABLE `civicrm_event`
  ADD `is_partial_payment` tinyint(4) DEFAULT '0' COMMENT 'is partial payment enabled for this event',
  ADD `min_initial_amount` decimal(20,2) DEFAULT NULL COMMENT 'Minimum initial amount for partial payment';

{if $multilingual}
  {foreach from=$locales item=loc}
    ALTER TABLE `civicrm_event`
      ADD `initial_amount_label_{$loc}` varchar(255) COLLATE utf8_unicode_ci COMMENT 'Initial amount label for partial payment',
      ADD `initial_amount_help_text_{$loc}` text COLLATE utf8_unicode_ci COMMENT 'Initial amount help text for partial payment';
  {/foreach}
{else}
  ALTER TABLE `civicrm_event`
    ADD `initial_amount_label` varchar(255) COLLATE utf8_unicode_ci COMMENT 'Initial amount label for partial payment',
    ADD `initial_amount_help_text` text COLLATE utf8_unicode_ci COMMENT 'Initial amount help text for partial payment';
{/if}

-- CRM-11347
UPDATE `civicrm_option_value` SET is_reserved = 0
WHERE name = 'Urgent' AND option_group_id = (SELECT id FROM `civicrm_option_group` WHERE name = 'case_status');

-- CRM-11400
UPDATE `civicrm_state_province` SET name = 'Distrito Federal' WHERE name = 'Diatrito Federal';

-- CRM-9379 and CRM-11539
SELECT @option_group_id_act := MAX(id) FROM civicrm_option_group WHERE name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_act;
SELECT @max_wt     := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @CompId     := MAX(id)     FROM civicrm_component where name = 'CiviContribute';

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, is_reserved, component_id, filter)
VALUES
 (@option_group_id_act, {localize field='label'}'Export Accounting Batch'{/localize}, @max_val+1, 'Export Accounting Batch', @max_wt+1, {localize field='description'}'Export Accounting Batch'{/localize}, 1, 1, @CompId, 1),
 (@option_group_id_act, {localize field='label'}'Create Batch'{/localize}, @max_val+2, 'Create Batch', @max_wt+2, {localize field='description'}'Create Batch'{/localize}, 1, 1, @CompId, 1),
 (@option_group_id_act, {localize field='label'}'Edit Batch'{/localize}, @max_val+3, 'Edit Batch', @max_wt+3, {localize field='description'}'Edit Batch'{/localize}, 1, 1, @CompId, 1);

-- CRM-11341
INSERT INTO
  `civicrm_job` (domain_id, run_frequency, last_run, name, description, api_entity, api_action, parameters, is_active)
SELECT
  id, 'Daily' , NULL, '{ts escape="sql" skip="true"}Disable expired relationships{/ts}', '{ts escape="sql" skip="true"}Disables relationships that have expired (ie. those relationships whose end date is in the past).{/ts}', 'job', 'disable_expired_relationships', NULL, 0
FROM `civicrm_domain`;

-- CRM-11367
SELECT @country_id   := max(id) from civicrm_country where name = 'Latvia';

DELETE FROM civicrm_state_province WHERE name IN ('Ventspils Apripkis', 'Aizkraukles Apripkis', 'Alkanes Apripkis', 'Balvu Apripkis', 'Bauskas Apripkis', 'Cesu Aprikis', 'Daugavpile Apripkis', 'Dobeles Apripkis', 'Gulbenes Aprlpkis', 'Jelgavas Apripkis', 'Jekabpils Apripkis', 'Kraslavas Apripkis', 'Kuldlgas Apripkis', 'Limbazu Apripkis', 'Liepajas Apripkis', 'Ludzas Apripkis', 'Madonas Apripkis', 'Ogres Apripkis', 'Preilu Apripkis', 'Rezaknes Apripkis', 'Rigas Apripkis', 'Saldus Apripkis', 'Talsu Apripkis', 'Tukuma Apriplcis', 'Valkas Apripkis', 'Valmieras Apripkis');

INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
(@country_id, '002', 'Aizkraukles novads'),
(@country_id, '038', 'Jaunjelgavas novads'),
(@country_id, '072', 'Pļaviņu novads'),
(@country_id, '046', 'Kokneses novads'),
(@country_id, '065', 'Neretas novads'),
(@country_id, '092', 'Skrīveru novads'),
(@country_id, '007', 'Alūksnes novads'),
(@country_id, '009', 'Apes novads'),
(@country_id, '015', 'Balvu novads'),
(@country_id, '108', 'Viļakas novads'),
(@country_id, '014', 'Baltinavas novads'),
(@country_id, '082', 'Rugāju novads'),
(@country_id, '016', 'Bauskas novads'),
(@country_id, '034', 'Iecavas novads'),
(@country_id, '083', 'Rundāles novads'),
(@country_id, '105', 'Vecumnieku novads'),
(@country_id, '022', 'Cēsu novads'),
(@country_id, '055', 'Līgatnes novads'),
(@country_id, '008', 'Amatas novads'),
(@country_id, '039', 'Jaunpiebalgas novads'),
(@country_id, '075', 'Priekuļu novads'),
(@country_id, '070', 'Pārgaujas novads'),
(@country_id, '076', 'Raunas novads'),
(@country_id, '104', 'Vecpiebalgas novads'),
(@country_id, '025', 'Daugavpils novads'),
(@country_id, '036', 'Ilūkstes novads'),
(@country_id, '026', 'Dobeles novads'),
(@country_id, '010', 'Auces novads'),
(@country_id, '098', 'Tērvetes novads'),
(@country_id, '033', 'Gulbenes novads'),
(@country_id, '041', 'Jelgavas novads'),
(@country_id, '069', 'Ozolnieku novads'),
(@country_id, '042', 'Jēkabpils novads'),
(@country_id, '004', 'Aknīstes novads'),
(@country_id, '107', 'Viesītes novads'),
(@country_id, '049', 'Krustpils novads'),
(@country_id, '085', 'Salas novads'),
(@country_id, '047', 'Krāslavas novads'),
(@country_id, '024', 'Dagdas novads'),
(@country_id, '001', 'Aglonas novads'),
(@country_id, '050', 'Kuldīgas novads'),
(@country_id, '093', 'Skrundas novads'),
(@country_id, '006', 'Alsungas novads'),
(@country_id, '003', 'Aizputes novads'),
(@country_id, '028', 'Durbes novads'),
(@country_id, '032', 'Grobiņas novads'),
(@country_id, '071', 'Pāvilostas novads'),
(@country_id, '074', 'Priekules novads'),
(@country_id, '066', 'Nīcas novads'),
(@country_id, '081', 'Rucavas novads'),
(@country_id, '100', 'Vaiņodes novads'),
(@country_id, '054', 'Limbažu novads'),
(@country_id, '005', 'Alojas novads'),
(@country_id, '086', 'Salacgrīvas novads'),
(@country_id, '058', 'Ludzas novads'),
(@country_id, '044', 'Kārsavas novads'),
(@country_id, '110', 'Zilupes novads'),
(@country_id, '023', 'Ciblas novads'),
(@country_id, '059', 'Madonas novads'),
(@country_id, '021', 'Cesvaines novads'),
(@country_id, '057', 'Lubānas novads'),
(@country_id, '102', 'Varakļānu novads'),
(@country_id, '030', 'Ērgļu novads'),
(@country_id, '067', 'Ogres novads'),
(@country_id, '035', 'Ikšķiles novads'),
(@country_id, '051', 'Ķeguma novads'),
(@country_id, '053', 'Lielvārdes novads'),
(@country_id, '073', 'Preiļu novads'),
(@country_id, '056', 'Līvānu novads'),
(@country_id, '078', 'Riebiņu novads'),
(@country_id, '103', 'Vārkavas novads'),
(@country_id, '077', 'Rēzeknes novads'),
(@country_id, '109', 'Viļānu novads'),
(@country_id, '013', 'Baldones novads'),
(@country_id, '052', 'Ķekavas novads'),
(@country_id, '068', 'Olaines novads'),
(@country_id, '087', 'Salaspils novads'),
(@country_id, '089', 'Saulkrastu novads'),
(@country_id, '091', 'Siguldas novads'),
(@country_id, '037', 'Inčukalna novads'),
(@country_id, '011', 'Ādažu novads'),
(@country_id, '012', 'Babītes novads'),
(@country_id, '020', 'Carnikavas novads'),
(@country_id, '031', 'Garkalnes novads'),
(@country_id, '048', 'Krimuldas novads'),
(@country_id, '061', 'Mālpils novads'),
(@country_id, '062', 'Mārupes novads'),
(@country_id, '080', 'Ropažu novads'),
(@country_id, '090', 'Sējas novads'),
(@country_id, '095', 'Stopiņu novads'),
(@country_id, '088', 'Saldus novads'),
(@country_id, '018', 'Brocēnu novads'),
(@country_id, '097', 'Talsu novads'),
(@country_id, '027', 'Dundagas novads'),
(@country_id, '063', 'Mērsraga novads'),
(@country_id, '079', 'Rojas novads'),
(@country_id, '099', 'Tukuma novads'),
(@country_id, '043', 'Kandavas novads'),
(@country_id, '029', 'Engures novads'),
(@country_id, '040', 'Jaunpils novads'),
(@country_id, '101', 'Valkas novads'),
(@country_id, '094', 'Smiltenes novads'),
(@country_id, '096', 'Strenču novads'),
(@country_id, '045', 'Kocēnu novads'),
(@country_id, '060', 'Mazsalacas novads'),
(@country_id, '084', 'Rūjienas novads'),
(@country_id, '017', 'Beverīnas novads'),
(@country_id, '019', 'Burtnieku novads'),
(@country_id, '064', 'Naukšēnu novads'),
(@country_id, '106', 'Ventspils novads'),
(@country_id, 'JKB', 'Jēkabpils'),
(@country_id, 'VMR', 'Valmiera');

-- CRM-11507
ALTER TABLE `civicrm_batch` CHANGE `type_id` `type_id` INT( 10 ) UNSIGNED NULL COMMENT 'fk to Batch Type options in civicrm_option_values';
UPDATE `civicrm_batch` SET `mode_id` = '1';

-- add Refunded in contribution status
SELECT @option_group_id_cs := MAX(id) FROM civicrm_option_group WHERE name = 'contribution_status';

SELECT @max_weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_cs;

INSERT INTO
  `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_cs, {localize}'{ts escape="sql"}Refunded{/ts}'{/localize}, @max_weight + 1, 'Refunded', NULL, 0, NULL, @max_weight + 1, 0, 1, 1, NULL, NULL);

-- Payprocs from extensions may have long titles
ALTER TABLE civicrm_payment_processor_type MODIFY COLUMN title varchar(127);

-- CRM-11665
ALTER TABLE `civicrm_address`
  ADD COLUMN manual_geo_code tinyint(4) DEFAULT '0' COMMENT 'Is this a manually entered geo code.';

-- CRM-11761
UPDATE `civicrm_setting` SET `group_name` = 'Personal Preferences' WHERE `group_name` = 'Navigation Menu';

-- CRM-11779

INSERT INTO civicrm_action_mapping ( entity, entity_value, entity_value_label, entity_status, entity_status_label, entity_date_start, entity_date_end, entity_recipient )
VALUES
( 'civicrm_participant', 'event_template', 'Event Template', 'civicrm_participant_status_type', 'Participant Status', 'event_start_date', 'event_end_date', 'event_contacts');

-- CRM-11802 Fix ON DELETE CASCADE constraint for line_item.price_field_id
ALTER TABLE `civicrm_line_item`
  DROP FOREIGN KEY `FK_civicrm_line_item_price_field_id`,
  CHANGE `price_field_id` `price_field_id` INT( 10 ) UNSIGNED DEFAULT NULL;

ALTER TABLE `civicrm_line_item`
  ADD CONSTRAINT `FK_civicrm_line_item_price_field_id` FOREIGN KEY (`price_field_id`) REFERENCES `civicrm_price_field`(id) ON DELETE SET NULL;

-- CRM-11821
-- update all location info of domain
-- like address, email, phone etc.
UPDATE civicrm_domain cd
LEFT JOIN civicrm_loc_block clb ON cd.loc_block_id = clb.id
LEFT JOIN civicrm_address ca ON clb.address_id = ca.id
LEFT JOIN civicrm_phone cp ON cp.id = clb.phone_id
LEFT JOIN civicrm_email ce ON ce.id = clb.email_id
SET
ca.contact_id = cd.contact_id, cp.contact_id = cd.contact_id, ce.contact_id = cd.contact_id;

-- Delete rows from civicrm_loc_block used for civicrm_domain
DELETE clb.* FROM civicrm_domain cd
LEFT JOIN civicrm_loc_block clb ON clb.id = cd.loc_block_id;

-- Delete loc_block_id from civicrm_domain
ALTER TABLE `civicrm_domain` DROP loc_block_id;

-- CRM11818
-- pledge payments should not be cancelled if the contribution was
-- compledged but the pledge is cancelled
UPDATE
civicrm_pledge_payment pp
INNER JOIN civicrm_contribution c ON
c.id = pp.contribution_id AND pp.status_id =3
AND contribution_status_id = 1
SET pp.status_id = contribution_status_id

