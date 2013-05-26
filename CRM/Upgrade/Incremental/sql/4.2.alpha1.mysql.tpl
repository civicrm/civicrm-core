{include file='../CRM/Upgrade/4.2.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-9542 mailing detail report template
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @mailCompId       := MAX(id)     FROM civicrm_component where name = 'CiviMail';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Mail Detail Report'{/localize}, 'mailing/detail', 'CRM_Report_Form_Mailing_Detail', @weight := @weight + 1, {localize}'Provides reporting on Intended and Successful Deliveries, Unsubscribes and Opt-outs, Replies and Forwards.'{/localize}, 1, @mailCompId);

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( {$domainID}, 'Mailing Detail Report', 'mailing/detail', 'Provides reporting on Intended and Successful Deliveries, Unsubscribes and Opt-outs, Replies and Forwards.', 'access CiviMail', '{literal}a:30:{s:6:"fields";a:6:{s:9:"sort_name";s:1:"1";s:12:"mailing_name";s:1:"1";s:11:"delivery_id";s:1:"1";s:14:"unsubscribe_id";s:1:"1";s:9:"optout_id";s:1:"1";s:5:"email";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:13:"mailing_id_op";s:2:"in";s:16:"mailing_id_value";a:0:{}s:18:"delivery_status_op";s:2:"eq";s:21:"delivery_status_value";s:0:"";s:18:"is_unsubscribed_op";s:2:"eq";s:21:"is_unsubscribed_value";s:0:"";s:12:"is_optout_op";s:2:"eq";s:15:"is_optout_value";s:0:"";s:13:"is_replied_op";s:2:"eq";s:16:"is_replied_value";s:0:"";s:15:"is_forwarded_op";s:2:"eq";s:18:"is_forwarded_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:9:"order_bys";a:1:{i:1;a:2:{s:6:"column";s:9:"sort_name";s:5:"order";s:3:"ASC";}}s:11:"description";s:21:"Mailing Detail Report";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:15:"access CiviMail";s:9:"parent_id";s:0:"";s:6:"groups";s:0:"";s:9:"domain_id";i:1;}{/literal}');

SELECT @reportlastID       := MAX(id) FROM civicrm_navigation where name = 'Reports' AND domain_id = {$domainID};
SELECT @nav_max_weight     := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql"}Mailing Detail Report{/ts}', 'Mailing Detail Report', 'access CiviMail', 'OR', @reportlastID, '1', NULL, @nav_max_weight+1 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

-- CRM-9600
ALTER TABLE `civicrm_custom_group` CHANGE `extends_entity_column_value` `extends_entity_column_value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'linking custom group for dynamic object.';

-- CRM-9534
ALTER TABLE `civicrm_prevnext_cache` ADD COLUMN is_selected tinyint(4) DEFAULT '0';

-- CRM-9834
-- civicrm_batch table changes
ALTER TABLE `civicrm_batch` ADD UNIQUE KEY `UI_name` ( name );


ALTER TABLE `civicrm_batch` ADD `saved_search_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to Saved Search ID';
ALTER TABLE `civicrm_batch` ADD `status_id` int(10) unsigned NOT NULL COMMENT 'fk to Batch Status options in civicrm_option_values';
ALTER TABLE `civicrm_batch` ADD `type_id` int(10) unsigned NOT NULL COMMENT 'fk to Batch Type options in civicrm_option_values';
ALTER TABLE `civicrm_batch` ADD `mode_id` int(10) unsigned DEFAULT NULL COMMENT 'fk to Batch mode options in civicrm_option_values';
ALTER TABLE `civicrm_batch` ADD `total` decimal(20,2) DEFAULT NULL COMMENT 'Total amount for this batch.';
ALTER TABLE `civicrm_batch` ADD `item_count` int(10) unsigned NOT NULL COMMENT 'Number of items in a batch.';

ALTER TABLE `civicrm_batch` ADD CONSTRAINT `FK_civicrm_batch_saved_search_id` FOREIGN KEY (`saved_search_id`) REFERENCES `civicrm_saved_search` (`id`) ON DELETE SET NULL;

--batch type and batch status option groups
INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}title{/localize}, `is_reserved`, `is_active`)
VALUES
  ('batch_type'          , {localize}'Batch Type'{/localize}                         , 1, 1),
  ('batch_status'        , {localize}'Batch Status'{/localize}                       , 1, 1);

SELECT @option_group_id_batch_type     := max(id) from civicrm_option_group where name = 'batch_type';
SELECT @option_group_id_batch_status   := max(id) from civicrm_option_group where name = 'batch_status';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`)
VALUES
   (@option_group_id_batch_type, {localize}'Contribution'{/localize}, 1, 'Contribution', NULL, 0, 0, 1),
   (@option_group_id_batch_type, {localize}'Membership'{/localize}, 2, 'Membership', NULL, 0, 0, 2),
   (@option_group_id_batch_status, {localize}'Open'{/localize}, 1, 'Open', NULL, 0, 0, 1),
   (@option_group_id_batch_status, {localize}'Closed'{/localize}, 2, 'Closed', NULL, 0, 0, 2);

--default profile for contribution and membership batch entry
INSERT INTO civicrm_uf_group
    ( name, group_type, {localize field='title'}title{/localize}, is_cms_user, is_reserved)
 VALUES
    ( 'contribution_batch_entry', 'Contribution', {localize}'Contribution Batch Entry'{/localize} , 0, 1),
    ( 'membership_batch_entry',   'Membership',   {localize}'Membership Batch Entry'{/localize} ,   0, 1);

SELECT @uf_group_contribution_batch_entry     := max(id) FROM civicrm_uf_group WHERE name = 'contribution_batch_entry';
SELECT @uf_group_membership_batch_entry       := max(id) FROM civicrm_uf_group WHERE name = 'membership_batch_entry';

INSERT INTO civicrm_uf_join
   (is_active, module, entity_table, entity_id, weight, uf_group_id)
VALUES
   (1, 'Profile', NULL, NULL, 9, @uf_group_contribution_batch_entry),
   (1, 'Profile', NULL, NULL, 9, @uf_group_membership_batch_entry);

INSERT INTO civicrm_uf_field
       ( uf_group_id, field_name,              is_required, is_reserved, weight, visibility,                  in_selector, is_searchable, location_type_id, {localize field='label'}label{/localize},  field_type )
VALUES
       ( @uf_group_contribution_batch_entry,     'contribution_type',           1, 1, 1, 'User and User Admin Only', 0, 0, NULL, {localize}'Type'{/localize}, 'Contribution'),
       ( @uf_group_contribution_batch_entry,     'total_amount',                1, 1, 2, 'User and User Admin Only', 0, 0, NULL, {localize}'Amount'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'contribution_status_id',      1, 1, 3, 'User and User Admin Only', 0, 0, NULL, {localize}'Status'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'receive_date',                1, 1, 4, 'User and User Admin Only', 0, 0, NULL, {localize}'Received'{/localize}, 'Contribution'),
       ( @uf_group_contribution_batch_entry,     'contribution_source',         0, 0, 5, 'User and User Admin Only', 0, 0, NULL, {localize}'Source'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'payment_instrument',          0, 0, 6, 'User and User Admin Only', 0, 0, NULL, {localize}'Payment Instrument'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'check_number',                0, 0, 7, 'User and User Admin Only', 0, 0, NULL, {localize}'Check Number'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'send_receipt',                0, 0, 8, 'User and User Admin Only', 0, 0, NULL, {localize}'Send Receipt'{/localize}, 'Contribution' ),
       ( @uf_group_contribution_batch_entry,     'invoice_id',                  0, 0, 9, 'User and User Admin Only', 0, 0, NULL, {localize}'Invoice ID'{/localize}, 'Contribution' ),
       ( @uf_group_membership_batch_entry,     'membership_type',             1, 1, 1, 'User and User Admin Only', 0, 0, NULL, {localize}'Type'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'join_date',                   1, 1, 2, 'User and User Admin Only', 0, 0, NULL, {localize}'Member Since'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'membership_start_date',       0, 1, 3, 'User and User Admin Only', 0, 0, NULL, {localize}'Start Date'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'membership_end_date',         0, 1, 4, 'User and User Admin Only', 0, 0, NULL, {localize}'End Date'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'membership_source',           0, 0, 5, 'User and User Admin Only', 0, 0, NULL, {localize}'Source'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'send_receipt',                0, 0, 6, 'User and User Admin Only', 0, 0, NULL, {localize}'Send Receipt'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'contribution_type',           1, 1, 7, 'User and User Admin Only', 0, 0, NULL, {localize}'Contribution Type'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'total_amount',                1, 1, 8, 'User and User Admin Only', 0, 0, NULL, {localize}'Amount'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'receive_date',                1, 1, 9, 'User and User Admin Only', 0, 0, NULL, {localize}'Received'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'payment_instrument',          0, 0, 10, 'User and User Admin Only', 0, 0, NULL, {localize}'Payment Instrument'{/localize}, 'Membership' ),
       ( @uf_group_membership_batch_entry,     'contribution_status_id',      1, 1, 11, 'User and User Admin Only', 0, 0, NULL, {localize}'Payment Status'{/localize}, 'Membership' );

--navigation menu entries

SELECT @navContributionsID := MAX(id) FROM civicrm_navigation where name = 'Contributions' AND domain_id = {$domainID};
SELECT @navMembershipsID := MAX(id) FROM civicrm_navigation where name = 'Memberships' AND domain_id = {$domainID};

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/batch&reset=1', '{ts escape="sql" skip="true"}Batches{/ts}', 'Batches', 'access CiviContribute,access CiviMember', '', @navContributionsID, '1', NULL, 4 ),
    ( {$domainID}, 'civicrm/batch&reset=1', '{ts escape="sql" skip="true"}Batches{/ts}', 'Batches', 'access CiviMember,acess CiviContribute',  '', @navMembershipsID, '1', NULL, 4 );

-- CRM-9686
INSERT INTO `civicrm_state_province`(`country_id`, `abbreviation`, `name`) VALUES(1097, "LP", "La Paz");

-- CRM-9905
ALTER TABLE civicrm_contribution_page CHANGE COLUMN is_email_receipt is_email_receipt TINYINT(4) DEFAULT 0;

-- CRM-9850
 ALTER TABLE `civicrm_contribution_page` CHANGE `payment_processor_id` `payment_processor` VARCHAR( 128 ) NULL DEFAULT NULL COMMENT 'Payment Processor for this contribution Page ';

 ALTER TABLE `civicrm_event` CHANGE `payment_processor_id` `payment_processor` VARCHAR( 128 ) NULL DEFAULT NULL COMMENT 'Payment Processor for this event ';

-- CRM-9783
CREATE TABLE `civicrm_sms_provider` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'SMS Provider ID',
  `name` varchar(64) DEFAULT NULL COMMENT 'Provider internal name points to option_value of option_group sms_provider_name',
  `title` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Provider name visible to user',
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_type` int(10) unsigned NOT NULL COMMENT 'points to value in civicrm_option_value for group sms_api_type',
  `api_url` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `api_params` text COLLATE utf8_unicode_ci COMMENT 'the api params in xml, http or smtp format',
  `is_default` tinyint(4) DEFAULT '0',
  `is_active` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

ALTER TABLE `civicrm_mailing` ADD `sms_provider_id` int(10) unsigned NULL COMMENT 'FK to civicrm_sms_provider id ';
ALTER TABLE `civicrm_mailing` ADD CONSTRAINT `FK_civicrm_mailing_sms_provider_id` FOREIGN KEY (`sms_provider_id`) REFERENCES `civicrm_sms_provider` (`id`) ON DELETE SET NULL;

INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
   ('sms_provider_name', {localize}'Sms provider Internal Name'{/localize} , 1, 1);
SELECT @option_group_id_sms_provider_name := max(id) from civicrm_option_group where name = 'sms_provider_name';

INSERT INTO civicrm_option_value
     (option_group_id, {localize field='label'}label{/localize}, value, name, weight, filter, is_default, component_id)
VALUES
     (@option_group_id_sms_provider_name, {localize}'Clickatell'{/localize}, 'Clickatell', 'Clickatell', 1, 0, NULL, NULL);

INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
    ( 'sms_api_type', {localize}'{ts escape="sql"}Api Type{/ts}'{/localize} , 1, 1 );
SELECT @option_group_id_sms_api_type := max(id) from civicrm_option_group where name = 'sms_api_type';

INSERT INTO civicrm_option_value
     (option_group_id, {localize field='label'}label{/localize}, value, name, weight, filter, is_default, is_reserved, component_id)
VALUES
     (@option_group_id_sms_api_type, {localize}'http'{/localize},  1, 'http',  1, NULL, 0, 1, NULL),
     (@option_group_id_sms_api_type, {localize}'xml'{/localize},   2, 'xml',   2, NULL, 0, 1, NULL),
     (@option_group_id_sms_api_type, {localize}'smtp'{/localize},  3, 'smtp',  3, NULL, 0, 1, NULL);

-- CRM-9784
SELECT @adminSystemSettingsID := MAX(id) FROM civicrm_navigation where name = 'System Settings' AND domain_id = {$domainID};

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/admin/sms/provider?reset=1', '{ts escape="sql" skip="true"}SMS Providers{/ts}', 'SMS Providers', 'administer CiviCRM', '', @adminSystemSettingsID, '1', NULL, 16 );

-- CRM-9799

SELECT @mailingsID := MAX(id) FROM civicrm_navigation where name = 'Mailings' AND domain_id = {$domainID};

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, 'civicrm/sms/send?reset=1', '{ts escape="sql" skip="true"}New SMS{/ts}', 'New SMS', 'administer CiviCRM', NULL, @mailingsID, '1', 1, 8 );

SELECT @fromEmailAddressesID := MAX(id) FROM civicrm_navigation where name = 'From Email Addresses' AND domain_id = {$domainID};

UPDATE civicrm_navigation SET has_separator = 1 WHERE parent_id = @mailingsID AND name = 'From Email Addresses';

SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_wt              := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_act, {localize}'BULK SMS'{/localize}, @max_wt, 'BULK SMS', NULL, 1, NULL, @max_wt, {localize}'BULK SMS'{/localize}, 0, 1, 1, NULL, NULL);

ALTER TABLE `civicrm_mailing_recipients` ADD `phone_id` int(10) unsigned DEFAULT NULL;

ALTER TABLE `civicrm_mailing_recipients` ADD CONSTRAINT `FK_civicrm_mailing_recipients_phone_id` FOREIGN KEY (`phone_id`) REFERENCES `civicrm_phone` (`id`) ON DELETE CASCADE;

ALTER TABLE `civicrm_mailing_event_queue` ADD `phone_id` int(10) unsigned DEFAULT NULL;

ALTER TABLE `civicrm_mailing_event_queue` ADD CONSTRAINT `FK_civicrm_mailing_event_queue_phone_id` FOREIGN KEY (`phone_id`) REFERENCES `civicrm_phone` (`id`) ON DELETE CASCADE;

ALTER TABLE `civicrm_mailing_event_queue` CHANGE `email_id` `email_id` int(10) unsigned DEFAULT NULL;
ALTER TABLE `civicrm_mailing_recipients` CHANGE `email_id` `email_id` int(10) unsigned DEFAULT NULL;

-- CRM-9982
ALTER TABLE `civicrm_contribution_page` ADD COLUMN is_confirm_enabled tinyint(4) DEFAULT '1';

-- CRM-9980
{if $multilingual}
  {foreach from=$locales item=locale}
  {if !$loc}
  {assign var=loc value="_$locale"}
  {/if}
    ALTER TABLE civicrm_group ADD title_{$locale} VARCHAR(64);
    ALTER TABLE civicrm_group ADD UNIQUE KEY `UI_title_{$locale}` (title_{$locale});

    ALTER TABLE `civicrm_batch` CHANGE `label_{$locale}` `title_{$locale}` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Friendly Name.';
    UPDATE civicrm_group SET title_{$locale} = title;

    ALTER TABLE civicrm_survey
     ADD COLUMN thankyou_title_{$locale} varchar(255)    COMMENT 'Title for Thank-you page (header title tag, and display at the top of the page).',
     ADD COLUMN thankyou_text_{$locale}  text    COMMENT 'text and html allowed. displayed above result on success page';
  {/foreach}

  ALTER TABLE civicrm_group DROP INDEX `UI_title`;
  ALTER TABLE civicrm_group DROP title;
{else}
  ALTER TABLE `civicrm_batch` CHANGE `label` `title` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Friendly Name.';
  ALTER TABLE civicrm_survey
     ADD COLUMN thankyou_title varchar(255)    COMMENT 'Title for Thank-you page (header title tag, and display at the top of the page).',
     ADD COLUMN thankyou_text  text    COMMENT 'text and html allowed. displayed above result on success page';
{/if}

-- CRM-9780
SELECT @country_id   := max(id) from civicrm_country where iso_code = "AN";
DELETE FROM civicrm_state_province WHERE country_id = @country_id;
DELETE FROM civicrm_country WHERE id = @country_id;

SELECT @region_id   := max(id) from civicrm_worldregion where name = "America South, Central, North and Caribbean";

INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Curaçao", "CW", @region_id, 0);
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Sint Maarten (Dutch Part)", "SX", @region_id, 0);
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Bonaire, Saint Eustatius and Saba", "BQ", @region_id, 0);

-- CRM-12428
{if $multilingual}
    {foreach from=$locales item=locale}
    	ALTER TABLE `civicrm_price_field_value` CHANGE label_{$locale} label_{$locale} VARCHAR(255)  NULL DEFAULT NULL;
    {/foreach}
{else}
	ALTER TABLE `civicrm_price_field_value` CHANGE `label` `label` VARCHAR(255)  NULL DEFAULT NULL;
{/if}

-- CRM-9714 create a default price set for contribution and membership
ALTER TABLE `civicrm_price_set`
ADD         `is_quick_config` TINYINT(4) NOT NULL DEFAULT '0'
                              COMMENT 'Is set if edited on Contribution or Event Page rather than through Manage Price Sets'
                              AFTER `contribution_type_id`,
ADD         `is_reserved`     TINYINT(4) DEFAULT '0'
                              COMMENT 'Is this a predefined system price set  (i.e. it can not be deleted, edited)?';

SELECT @contribution_type_id := max(id) FROM `civicrm_contribution_type` WHERE `name` = 'Member Dues';
INSERT INTO `civicrm_price_set` ( `name`, {localize field='title'}`title`{/localize}, `is_active`, `extends`, `is_quick_config`, `is_reserved`, `contribution_type_id`)
VALUES ( 'default_contribution_amount', {localize}'Contribution Amount'{/localize}, '1', '2', '1', '1', null),
 ( 'default_membership_type_amount', {localize}'Membership Amount'{/localize}, '1', '3', '1', '1', @contribution_type_id);

SELECT @setID := max(id) FROM civicrm_price_set WHERE name = 'default_contribution_amount' AND extends = 2 AND is_quick_config = 1 ;

INSERT INTO `civicrm_price_field` (`price_set_id`, `name`, {localize field='label'}`label`{/localize}, `html_type`,`weight`, `is_display_amounts`, `options_per_line`, `is_active`, `is_required`,`visibility_id` )
VALUES ( @setID, 'contribution_amount', {localize}'Contribution Amount'{/localize}, 'Text', '1', '1', '1', '1', '1', '1' );

SELECT @fieldID := max(id) FROM civicrm_price_field WHERE name = 'contribution_amount' AND price_set_id = @setID;

INSERT INTO `civicrm_price_field_value` (  `price_field_id`, `name`, {localize field='label'}`label`{/localize}, `amount`, `weight`, `is_default`, `is_active`)
VALUES ( @fieldID, 'contribution_amount', {localize}'Contribution Amount'{/localize}, '1', '1', '0', '1');
ALTER TABLE `civicrm_custom_group` CHANGE `extends_entity_column_value` `extends_entity_column_value` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'linking custom group for dynamic object.';

-- CRM-9714 create price fields for all membershiptype
SELECT @setID := max(id) FROM civicrm_price_set WHERE name = 'default_membership_type_amount' AND extends = 3 AND is_quick_config = 1 ;

INSERT INTO civicrm_price_field ( price_set_id, name, {localize field='label'}label{/localize}, html_type, is_display_amounts, is_required )
SELECT @setID as price_set_id,  cmt.member_of_contact_id as name, {localize}'Membership Amount'{/localize}, 'Radio' as html_type, 0 as is_display_amounts, 0 as is_required
FROM `civicrm_membership_type` cmt
GROUP BY cmt.member_of_contact_id;

INSERT INTO civicrm_price_field_value ( price_field_id, name, {localize field='label'}label{/localize}, {localize field='description'}description{/localize}, amount, membership_type_id)
SELECT
cpf.id, cmt.name{$loc} as label1, {localize field='name'}cmt.name as label2{/localize},{localize field='description'}cmt.description{/localize}, cmt.minimum_fee, cmt.id
FROM `civicrm_membership_type` cmt
LEFT JOIN civicrm_price_field cpf ON cmt.member_of_contact_id = BINARY cpf.name;

-- CRM-9714
SELECT @fieldID := cpf.id, @fieldValueID := cpfv.id FROM civicrm_price_set cps
LEFT JOIN civicrm_price_field cpf ON  cps.id = cpf.price_set_id
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
WHERE cps.name = 'default_contribution_amount';

INSERT INTO civicrm_line_item ( entity_table, entity_id, price_field_id, label, qty, unit_price, line_total, participant_count, price_field_value_id )
SELECT 'civicrm_contribution', cc.id, @fieldID, 'Contribution Amount', 1, total_amount, total_amount , 0, @fieldValueID
FROM `civicrm_contribution` cc
LEFT JOIN civicrm_line_item cli ON cc.id=cli.entity_id and cli.entity_table = 'civicrm_contribution'
LEFT JOIN civicrm_membership_payment cmp ON cc.id = cmp.contribution_id
LEFT JOIN civicrm_participant_payment cpp ON cc.id = cpp.contribution_id
WHERE cli.entity_id IS NULL AND cc.contribution_page_id IS NULL AND cmp.contribution_id IS NULL AND cpp.contribution_id IS NULL
GROUP BY cc.id;

-- CRM-10071 contribution membership detail report template
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @memberCompId := max(id) FROM civicrm_component where name = 'CiviMember';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Contribution and Membership Details'{/localize}, 'member/contributionDetail', 'CRM_Report_Form_Member_ContributionDetail', @weight := @weight + 1, {localize}'Contribution details for any type of contribution, plus associated membership information for contributions which are in payment for memberships.'{/localize}, 1, @memberCompId);

INSERT INTO `civicrm_report_instance`
    ( `domain_id`, `title`, `report_id`, `description`, `permission`, `form_values`)
VALUES
    ( {$domainID}, 'Contribution and Membership Details', 'member/contributionDetail', 'Contribution details for any type of contribution, plus associated membership information for contributions which are in payment for memberships.', 'access CiviMember', '{literal}a:67:{s:6:"fields";a:12:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:9:"join_date";s:1:"1";s:22:"membership_status_name";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:30:"membership_start_date_relative";s:1:"0";s:26:"membership_start_date_from";s:0:"";s:24:"membership_start_date_to";s:0:"";s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:12:"county_id_op";s:2:"in";s:15:"county_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:35:"Contribution and Membership Details";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:17:"access CiviMember";s:9:"domain_id";i:1;}{s:6:"fields";a:12:{s:9:"sort_name";s:1:"1";s:5:"email";s:1:"1";s:5:"phone";s:1:"1";s:20:"contribution_type_id";s:1:"1";s:12:"receive_date";s:1:"1";s:12:"total_amount";s:1:"1";s:18:"membership_type_id";s:1:"1";s:21:"membership_start_date";s:1:"1";s:19:"membership_end_date";s:1:"1";s:9:"join_date";s:1:"1";s:22:"membership_status_name";s:1:"1";s:10:"country_id";s:1:"1";}s:12:"sort_name_op";s:3:"has";s:15:"sort_name_value";s:0:"";s:6:"id_min";s:0:"";s:6:"id_max";s:0:"";s:5:"id_op";s:3:"lte";s:8:"id_value";s:0:"";s:21:"receive_date_relative";s:1:"0";s:17:"receive_date_from";s:0:"";s:15:"receive_date_to";s:0:"";s:23:"contribution_type_id_op";s:2:"in";s:26:"contribution_type_id_value";a:0:{}s:24:"payment_instrument_id_op";s:2:"in";s:27:"payment_instrument_id_value";a:0:{}s:25:"contribution_status_id_op";s:2:"in";s:28:"contribution_status_id_value";a:0:{}s:16:"total_amount_min";s:0:"";s:16:"total_amount_max";s:0:"";s:15:"total_amount_op";s:3:"lte";s:18:"total_amount_value";s:0:"";s:6:"gid_op";s:2:"in";s:9:"gid_value";a:0:{}s:13:"ordinality_op";s:2:"in";s:16:"ordinality_value";a:0:{}s:18:"join_date_relative";s:1:"0";s:14:"join_date_from";s:0:"";s:12:"join_date_to";s:0:"";s:30:"membership_start_date_relative";s:1:"0";s:26:"membership_start_date_from";s:0:"";s:24:"membership_start_date_to";s:0:"";s:28:"membership_end_date_relative";s:1:"0";s:24:"membership_end_date_from";s:0:"";s:22:"membership_end_date_to";s:0:"";s:23:"owner_membership_id_min";s:0:"";s:23:"owner_membership_id_max";s:0:"";s:22:"owner_membership_id_op";s:3:"lte";s:25:"owner_membership_id_value";s:0:"";s:6:"tid_op";s:2:"in";s:9:"tid_value";a:0:{}s:6:"sid_op";s:2:"in";s:9:"sid_value";a:0:{}s:17:"street_number_min";s:0:"";s:17:"street_number_max";s:0:"";s:16:"street_number_op";s:3:"lte";s:19:"street_number_value";s:0:"";s:14:"street_name_op";s:3:"has";s:17:"street_name_value";s:0:"";s:15:"postal_code_min";s:0:"";s:15:"postal_code_max";s:0:"";s:14:"postal_code_op";s:3:"lte";s:17:"postal_code_value";s:0:"";s:7:"city_op";s:3:"has";s:10:"city_value";s:0:"";s:12:"county_id_op";s:2:"in";s:15:"county_id_value";a:0:{}s:20:"state_province_id_op";s:2:"in";s:23:"state_province_id_value";a:0:{}s:13:"country_id_op";s:2:"in";s:16:"country_id_value";a:0:{}s:8:"tagid_op";s:2:"in";s:11:"tagid_value";a:0:{}s:11:"description";s:35:"Contribution and Membership Details";s:13:"email_subject";s:0:"";s:8:"email_to";s:0:"";s:8:"email_cc";s:0:"";s:10:"permission";s:1:"0";s:9:"domain_id";i:1;}{/literal}');

SELECT @reportlastID       := MAX(id) FROM civicrm_navigation where name = 'Reports' AND domain_id = {$domainID};
SELECT @nav_max_weight     := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @reportlastID;

SET @instanceID:=LAST_INSERT_ID();
INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( {$domainID}, CONCAT('civicrm/report/instance/', @instanceID,'&reset=1'), '{ts escape="sql" skip="true"}Contribution and Membership Details{/ts}', 'Contribution and Membership Details', 'access CiviMember', 'AND', @reportlastID, '1', NULL, @instanceID+2 );
UPDATE civicrm_report_instance SET navigation_id = LAST_INSERT_ID() WHERE id = @instanceID;

-- CRM-9936
ALTER TABLE civicrm_group ADD is_reserved TINYINT( 4 ) NULL DEFAULT '0' ;

-- CRM-9501 price set report templates
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @CompId       := MAX(id)     FROM civicrm_component where name = 'CiviContribute';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Line Item Report'{/localize}, 'price/lineitem', 'CRM_Report_Form_Price_Lineitem', @weight := @weight + 1, {localize}'Price set report by line item.'{/localize}, 1, @CompId);
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Contribution Report with Line Item information'{/localize}, 'price/contributionbased', 'CRM_Report_Form_Price_Contributionbased', @weight := @weight + 1, {localize}'Contribution Line Item Report.'{/localize}, 1, @CompId);
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) VALUES
  (@option_group_id_report, {localize}'Participant Report with Line Item information'{/localize}, 'price/lineitemparticipant', 'CRM_Report_Form_Price_Lineitemparticipant', @weight := @weight + 1, {localize}'Participant Line Item Report.'{/localize}, 1, @CompId);

-- CRM-10148
ALTER TABLE civicrm_report_instance ADD is_reserved TINYINT( 4 ) NULL DEFAULT '0' ;

-- CRM-9438
SELECT @option_group_id_act := MAX(id)     FROM civicrm_option_group WHERE name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_act;
SELECT @max_wt     := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @CompId     := MAX(id)     FROM civicrm_component where name = 'CiviMember';
INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, is_reserved, component_id)
VALUES
  (@option_group_id_act, {localize field='label'}'Change Membership Status'{/localize}, (SELECT @max_val := @max_val+1), 'Change Membership Status', (SELECT @max_wt := @max_wt+1), {localize field='description'}'Change Membership Status.'{/localize}, 1, 1, @CompId),
  (@option_group_id_act, {localize field='label'}'Change Membership Type'{/localize}, (SELECT @max_val := @max_val+1), 'Change Membership Type', (SELECT @max_wt := @max_wt+1), {localize field='description'}'Change Membership Type.'{/localize}, 1, 1, @CompId);

-- CRM-10084
INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
   (@option_group_id_act, {localize}'Cancel Recurring Contribution'{/localize}, (SELECT @max_val := @max_val+1), 'Cancel Recurring Contribution', NULL,1, 0, (SELECT @max_wt := @max_wt+1), {localize}''{/localize}, 0, 1, 1, NULL, NULL);

-- CRM-10090
INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_act, {localize}'Update Recurring Contribution Billing Details'{/localize}, (SELECT @max_val := @max_val+1), 'Update Recurring Contribution Billing Details', NULL,1, 0, (SELECT @max_wt := @max_wt+1), {localize}''{/localize}, 0, 1, 1, NULL, NULL),
(@option_group_id_act, {localize}'Update Recurring Contribution'{/localize}, (SELECT @max_val := @max_val+1), 'Update Recurring Contribution', NULL,1, 0, (SELECT @max_wt := @max_wt+1), {localize}''{/localize}, 0, 1, 1, NULL, NULL);

-- CRM-10117
ALTER TABLE `civicrm_price_field_value` CHANGE `is_active` `is_active` TINYINT( 4 ) NULL DEFAULT '1' COMMENT 'Is this price field value active';


-- CRM-8359
INSERT INTO
   `civicrm_action_mapping` (`entity`, `entity_value`, `entity_value_label`, `entity_status`, `entity_status_label`, `entity_date_start`, `entity_date_end`, `entity_recipient`) VALUES
   ('civicrm_membership', 'civicrm_membership_type', 'Membership Type', 'auto_renew_options', 'Auto Renew Options', 'membership_join_date', 'membership_end_date', NULL);

INSERT INTO
   `civicrm_option_group` (`name`, {localize field='title'}title{/localize}, `is_reserved`, `is_active`)
VALUES
  ('auto_renew_options', {localize}'Auto Renew Options'{/localize}, 1, 1);

SELECT @option_group_id_aro := max(id) from civicrm_option_group where name = 'auto_renew_options';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`)
VALUES
   (@option_group_id_aro, {localize}'Renewal Reminder (non-auto-renew memberships only)'{/localize}, 1, 'Renewal Reminder (non-auto-renew memberships only)', NULL, 0, 0, 1),
   (@option_group_id_aro, {localize}'Auto-renew Memberships Only'{/localize}, 2, 'Auto-renew Memberships Only', NULL, 0, 0, 2),
   (@option_group_id_aro, {localize}'Reminder for Both'{/localize}, 3, 'Reminder for Both', NULL, 0, 0, 3);

-- CRM-10335, truncate cache to begin to speed the alter table
TRUNCATE civicrm_cache;
ALTER TABLE civicrm_cache
  DROP INDEX UI_group_path,
  ADD UNIQUE INDEX `UI_group_path_date` (`group_name`, `path`, `created_date`);

-- CRM-10337
ALTER TABLE civicrm_survey
  ADD COLUMN bypass_confirm tinyint(4) DEFAULT '0' COMMENT 'Used to store option group id.';
