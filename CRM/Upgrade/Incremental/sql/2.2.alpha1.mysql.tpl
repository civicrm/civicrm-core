-- /************************************************************************
-- *
-- * MySQL Script for civicrm database/tables - upgradation from 2.1 -> 2.2
-- *
-- *************************************************************************/

-- Please add script for all the schema / fixed-data related modifications to 
-- this sql script as you resolve 2.2 issues. Include the issue number which 
-- is the source of the change, as part of the comment.

-- fix version column first
ALTER TABLE `civicrm_domain` 
  MODIFY version varchar(32) COMMENT 'The civicrm version this instance is running';

-- make the register_by_id cascade in civicrm_participant	

ALTER TABLE `civicrm_participant`
   DROP FOREIGN KEY `FK_civicrm_participant_registered_by_id`;
ALTER TABLE `civicrm_participant`
    ADD CONSTRAINT `FK_civicrm_participant_registered_by_id` FOREIGN KEY (`registered_by_id`) REFERENCES `civicrm_participant` (`id`) ON DELETE CASCADE;


-- merge civicrm_event_page to civicrm_event

ALTER TABLE `civicrm_event`
  {if $multilingual}
    {foreach from=$locales item=locale}
      ADD `intro_text_{$locale}` text collate utf8_unicode_ci,
      ADD `footer_text_{$locale}` text collate utf8_unicode_ci,
      ADD `confirm_title_{$locale}` varchar(255) collate utf8_unicode_ci default NULL,
      ADD `confirm_text_{$locale}` text collate utf8_unicode_ci,
      ADD `confirm_footer_text_{$locale}` text collate utf8_unicode_ci,
      ADD `confirm_email_text_{$locale}` text collate utf8_unicode_ci,
      ADD `confirm_from_name_{$locale}` varchar(255) collate utf8_unicode_ci default NULL,
      ADD `thankyou_title_{$locale}` varchar(255) collate utf8_unicode_ci default NULL,
      ADD `thankyou_text_{$locale}` text collate utf8_unicode_ci,
      ADD `thankyou_footer_text_{$locale}` text collate utf8_unicode_ci,
      ADD `pay_later_text_{$locale}` text collate utf8_unicode_ci,
      ADD `pay_later_receipt_{$locale}` text collate utf8_unicode_ci,
    {/foreach}
  {else}
    ADD `intro_text` text collate utf8_unicode_ci COMMENT 'Introductory message for Event Registration page. Text and html allowed. Displayed at the top of Event Registration form.',
    ADD `footer_text` text collate utf8_unicode_ci COMMENT 'Footer message for Event Registration page. Text and html allowed. Displayed at the bottom of Event Registration form.',
    ADD `confirm_title` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Title for Confirmation page.',
    ADD `confirm_text` text collate utf8_unicode_ci COMMENT 'Introductory message for Event Registration page. Text and html allowed. Displayed at the top of Event Registration form.',
    ADD `confirm_footer_text` text collate utf8_unicode_ci COMMENT 'Footer message for Event Registration page. Text and html allowed. Displayed at the bottom of Event Registration form.',
    ADD `confirm_email_text` text collate utf8_unicode_ci COMMENT 'text to include above standard event info on confirmation email. emails are text-only, so do not allow html for now',
    ADD `confirm_from_name` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'FROM email name used for confirmation emails.',
    ADD `thankyou_title` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'Title for ThankYou page.',
    ADD `thankyou_text` text collate utf8_unicode_ci COMMENT 'ThankYou Text.',
    ADD `thankyou_footer_text` text collate utf8_unicode_ci COMMENT 'Footer message.',
    ADD `pay_later_text` text collate utf8_unicode_ci COMMENT 'The text displayed to the user in the main form',
    ADD `pay_later_receipt` text collate utf8_unicode_ci COMMENT 'The receipt sent to the user instead of the normal receipt text',
  {/if}
   ADD `is_email_confirm` tinyint(4) default '0' COMMENT 'If true, confirmation is automatically emailed to contact on successful registration.',
   ADD `confirm_from_email` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'FROM email address used for confirmation emails.',
   ADD `cc_confirm` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'comma-separated list of email addresses to cc each time a confirmation is sent',
   ADD `bcc_confirm` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'comma-separated list of email addresses to bcc each time a confirmation is sent',
   ADD `default_fee_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_option_value.',
   ADD `default_discount_id` int(10) unsigned default NULL COMMENT 'FK to civicrm_option_value.',
   ADD `is_pay_later` tinyint(4) default '0' COMMENT 'if true - allows the user to send payment directly to the org later',
   ADD `is_multiple_registrations` tinyint(4) default '0' COMMENT 'if true - allows the user to register multiple participants for event',
   ALTER `max_participants`  DROP DEFAULT;

UPDATE civicrm_event ce
LEFT JOIN civicrm_event_page cp ON ce.id=cp.event_id
SET
  {if $multilingual}
    {foreach from=$locales item=locale}
      ce.intro_text_{$locale} = cp.intro_text_{$locale},
      ce.footer_text_{$locale} = cp.footer_text_{$locale},
      ce.confirm_title_{$locale} = cp.confirm_title_{$locale},	
      ce.confirm_text_{$locale} = cp.confirm_text_{$locale},
      ce.confirm_footer_text_{$locale} = cp.confirm_footer_text_{$locale},
      ce.confirm_email_text_{$locale} = cp.confirm_email_text_{$locale},
      ce.confirm_from_name_{$locale} = cp.confirm_from_name_{$locale},
      ce.thankyou_title_{$locale} = cp.thankyou_title_{$locale},
      ce.thankyou_text_{$locale} = cp.thankyou_text_{$locale},
      ce.thankyou_footer_text_{$locale} = cp.thankyou_footer_text_{$locale},
      ce.pay_later_text_{$locale} = cp.pay_later_text_{$locale},
      ce.pay_later_receipt_{$locale} = cp.pay_later_receipt_{$locale},
    {/foreach}
  {else}
    ce.intro_text = cp.intro_text,
    ce.footer_text = cp.footer_text,
    ce.confirm_title = cp.confirm_title,	
    ce.confirm_text = cp.confirm_text,
    ce.confirm_footer_text = cp.confirm_footer_text,
    ce.confirm_email_text = cp.confirm_email_text,
    ce.confirm_from_name = cp.confirm_from_name,
    ce.thankyou_title = cp.thankyou_title,
    ce.thankyou_text = cp.thankyou_text,
    ce.thankyou_footer_text = cp.thankyou_footer_text,
    ce.pay_later_text = cp.pay_later_text,
    ce.pay_later_receipt = cp.pay_later_receipt,
  {/if}
    ce.is_email_confirm = cp.is_email_confirm,
    ce.confirm_from_email = cp.confirm_from_email,
    ce.cc_confirm = cp.cc_confirm,
    ce.bcc_confirm = cp.bcc_confirm,
    ce.default_fee_id = cp.default_fee_id,
    ce.default_discount_id = cp.default_discount_id,
    ce.is_pay_later = cp.is_pay_later,
    ce.is_multiple_registrations = cp.is_multiple_registrations;

-- CRM-3391
-- Update table name in civicrm_option_group (Fee Level) 
-- cleanup for unused option group

DELETE og.* FROM civicrm_option_group og 
 LEFT JOIN civicrm_event_page ep ON ep.id = SUBSTRING_INDEX( SUBSTRING( og.name, 27 ) , '.discount', 1) 
 WHERE og.name LIKE 'civicrm_event_page.amount%' AND ep.id IS NULL;

{foreach from =$eventFees item=ogid}

  SELECT @option_group_id := id ,@event_page_id := SUBSTRING_INDEX( SUBSTRING( name, 27 ) , '.discount', 1 )
    FROM civicrm_option_group WHERE `name` LIKE 'civicrm_event_page.amount%' LIMIT 1;

  SELECT @event_id := ep.event_id FROM civicrm_event_page ep WHERE ep.id = @event_page_id;

  UPDATE `civicrm_option_group`
    SET `name` = REPLACE( name, CONCAT_WS('.', 'civicrm_event_page.amount', @event_page_id ), CONCAT_WS('.', 'civicrm_event.amount', @event_id ) )  
    WHERE `id` = @option_group_id;
{/foreach}

-- Update table entity_table and entity_id civicrm_tell_friend 
UPDATE civicrm_tell_friend tf
  SET tf.`entity_table` = 'civicrm_event',
      tf.`entity_id` = (SELECT e.`event_id` FROM civicrm_event_page e WHERE e.`id` = tf.`entity_id`)
  WHERE tf.`entity_table`='civicrm_event_page' ;

--CRM-4256
-- Update table name in civicrm_price_set_entity (Price Set) 
UPDATE civicrm_price_set_entity pse
  SET pse.`entity_table`='civicrm_event',
      pse.`entity_id` = (SELECT e.`event_id` FROM civicrm_event_page e WHERE e.`id` = pse.`entity_id`)
  WHERE pse.`entity_table`='civicrm_event_page';

-- /*******************************************************
-- *
-- * Drop civicrm_event_page table
-- *
-- *******************************************************/

DROP TABLE civicrm_event_page;

-- CRM-3546
{if $multilingual}
  INSERT INTO civicrm_option_group (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active) VALUES ('visibility', {foreach from=$locales item=locale}'Visibility',{/foreach} 0, 1);

{else}
  INSERT INTO civicrm_option_group (name, description, is_reserved, is_active) VALUES ('visibility', 'Visibility', 0, 1);
{/if}

SELECT @option_group_id_vis := max(id) from civicrm_option_group where name = 'visibility';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,      {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,     weight) VALUES
    (@option_group_id_vis, {foreach from=$locales item=locale}'Public',{/foreach}        1,     'public', 1),
    (@option_group_id_vis, {foreach from=$locales item=locale}'Admin',{/foreach}         2,     'admin',  2);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,      label,    value, name,     weight) VALUES
    (@option_group_id_vis, 'Public', 1,     'public', 1),
    (@option_group_id_vis, 'Admin',  2,     'admin',  2);
{/if}

ALTER TABLE civicrm_option_value
  ADD visibility_id int unsigned default NULL;


-- * A Personal Campaign Page Block stores admin configurable status options and rules

CREATE TABLE civicrm_pcp_block (
     id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'PCP block Id',
     entity_table varchar(64)    ,
     entity_id int unsigned NOT NULL   COMMENT 'FK to civicrm_contribution_page.id',
     supporter_profile_id int unsigned   DEFAULT NULL COMMENT 'Does Personal Campaign Page require manual activation by administrator? (is inactive by default after setup)?',
     is_approval_needed tinyint   DEFAULT NULL COMMENT 'Does Personal Campaign Page require manual activation by administrator? (is inactive by default after setup)?',
     is_tellfriend_enabled tinyint   DEFAULT NULL COMMENT 'Does Personal Campaign Page allow using tell a friend?',
     tellfriend_limit int unsigned   DEFAULT NULL COMMENT 'Maximum recipient fields allowed in tell a friend',
     link_text varchar(255)   DEFAULT NULL COMMENT 'Link text for PCP.',
     is_active tinyint   DEFAULT 1 COMMENT 'Is Personal Campaign Page Block enabled/active?',
     PRIMARY KEY ( id ),
     CONSTRAINT FK_civicrm_pcp_block_entity_id FOREIGN KEY (entity_id) REFERENCES civicrm_contribution_page(id)   
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- * civicrm_pcp

CREATE TABLE civicrm_pcp (
     id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Personal Campaign Page ID',
     contact_id int unsigned NOT NULL   COMMENT 'FK to Contact ID',
     status_id int unsigned NOT NULL   ,
     title varchar(255)   DEFAULT NULL ,
     intro_text text   DEFAULT NULL ,
     page_text text   DEFAULT NULL ,
     donate_link_text varchar(255)   DEFAULT NULL ,
     contribution_page_id int unsigned NOT NULL   COMMENT 'The Contribution Page which triggered this pcp',
     is_thermometer int unsigned   DEFAULT 0 ,
     is_honor_roll int unsigned   DEFAULT 0 ,
     goal_amount decimal(20,2)    COMMENT 'Goal amount of this Personal Campaign Page.',
     referer varchar(255)   DEFAULT NULL ,
     is_active tinyint   DEFAULT 0 COMMENT 'Is Personal Campaign Page enabled/active?',
     PRIMARY KEY ( id ),      
     CONSTRAINT FK_civicrm_pcp_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE,      
     CONSTRAINT FK_civicrm_pcp_contribution_page_id FOREIGN KEY (contribution_page_id) REFERENCES civicrm_contribution_page(id)   
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- * civicrm_contribution_soft

CREATE TABLE civicrm_contribution_soft (
     id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Soft Contribution ID',
     contribution_id int unsigned NOT NULL   COMMENT 'FK to contribution table.',
     contact_id int unsigned NOT NULL   COMMENT 'FK to Contact ID',
     amount decimal(20,2) NOT NULL   COMMENT 'Amount of this soft contribution.',
     pcp_id int unsigned   DEFAULT NULL ,
     pcp_display_in_roll tinyint   DEFAULT 0 ,
     pcp_roll_nickname varchar(255)   DEFAULT NULL ,
     pcp_personal_note varchar(255)   DEFAULT NULL ,
     PRIMARY KEY ( id ) ,
     INDEX index_id( pcp_id ) ,      
     CONSTRAINT FK_civicrm_contribution_soft_contribution_id FOREIGN KEY (contribution_id) REFERENCES civicrm_contribution(id) ON DELETE CASCADE,      
     CONSTRAINT FK_civicrm_contribution_soft_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;


-- fixed for CRM-2105 Greeting Type
{if $multilingual}
  INSERT INTO civicrm_option_group (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active) VALUES ('greeting_type', {foreach from=$locales item=locale}'Greeting Type',{/foreach} 0, 1);
{else}
  INSERT INTO civicrm_option_group (name, description, is_reserved, is_active ) VALUES ('greeting_type', 'Greeting Type', 0, 1 );
{/if}

SELECT @option_group_id_gr  := max(id) from civicrm_option_group where name = 'greeting_type';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,     {foreach from=$locales item=locale}label_{$locale},{/foreach}                value, name,                           is_default, weight, is_reserved) VALUES
    (@option_group_id_gr, {foreach from=$locales item=locale}'Dear [first]',{/foreach}                 1,     'Dear [first]',                 1,          1,      0),
    (@option_group_id_gr, {foreach from=$locales item=locale}'Dear [prefix] [first] [last]',{/foreach} 2,     'Dear [prefix] [first] [last]', 0,          2,      0),
    (@option_group_id_gr, {foreach from=$locales item=locale}'Dear [prefix] [last]',{/foreach}         3,     'Dear [prefix] [last]',         0,          3,      0),
    (@option_group_id_gr, {foreach from=$locales item=locale}'Customized',{/foreach}                   4,     'Customized',                   0,          4,      1);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,     label,                          value, name,                           is_default, weight, is_reserved) VALUES
    (@option_group_id_gr, 'Dear [first]',                 1,     'Dear [first]',                 1,          1,      0),
    (@option_group_id_gr, 'Dear [prefix] [first] [last]', 2,     'Dear [prefix] [first] [last]', 0,          2,      0),
    (@option_group_id_gr, 'Dear [prefix] [last]',         3,     'Dear [prefix] [last]',         0,          3,      0),
    (@option_group_id_gr, 'Customized',                   4,     'Customized',                   0,          4,      1);
{/if}

ALTER TABLE `civicrm_contact` ADD `greeting_type_id` int(10) unsigned NULL DEFAULT NULL AFTER greeting_type;

SELECT @greetingvalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'greeting_type' && civicrm_option_value.name = 'Dear [first]';
UPDATE civicrm_contact SET greeting_type_id = @greetingvalue WHERE civicrm_contact.greeting_type = 'Informal';

SELECT @greetingvalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'greeting_type' && civicrm_option_value.name = 'Dear [prefix] [first] [last]';
UPDATE civicrm_contact SET greeting_type_id = @greetingvalue WHERE civicrm_contact.greeting_type = 'Formal';

SELECT @greetingvalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'greeting_type' && civicrm_option_value.name = 'Dear [prefix] [last]';
UPDATE civicrm_contact SET greeting_type_id = @greetingvalue WHERE civicrm_contact.greeting_type = 'Honorific';

SELECT @greetingvalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'greeting_type' && civicrm_option_value.name = 'Customized';
UPDATE civicrm_contact SET greeting_type_id = @greetingvalue WHERE civicrm_contact.greeting_type = 'Custom';


ALTER TABLE `civicrm_contact` DROP `greeting_type`;


-- Add 'Address Edit option IM  & Open ID
-- CRM- 3419

SELECT @option_group_id_ao := max(id) from civicrm_option_group where name = 'address_options';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,     {foreach from=$locales item=locale}label_{$locale},{/foreach}      value, name,     weight, is_reserved) VALUES
    (@option_group_id_ao, {foreach from=$locales item=locale}'Instant Messenger',{/foreach}  13,    'im',     13,     1),
    (@option_group_id_ao, {foreach from=$locales item=locale}'OpenID',{/foreach}             14,    'openid', 14,     1);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,     label,                value, name,     weight, is_reserved) VALUES
    (@option_group_id_ao, 'Instant Messenger',  13,    'im',     13,     1),
    (@option_group_id_ao, 'OpenID',             14,    'openid', 14,     1);
{/if}

UPDATE civicrm_preferences SET address_options  = CONCAT(address_options, '1314');


-- * Fix for CRM-3248
{if $multilingual}
  INSERT INTO civicrm_option_group (name, {foreach from=$locales item=locale}description_{$locale},{/foreach} is_reserved, is_active ) VALUES ('phone_type', {foreach from=$locales item=locale}'Phone Type',{/foreach} 0, 1);
{else}
  INSERT INTO civicrm_option_group (name, description, is_reserved, is_active) VALUES ('phone_type', 'Phone Type', 0, 1);
{/if}

SELECT @option_group_id_pt := max(id) from civicrm_option_group where name = 'phone_type';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,     {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,        weight) VALUES
    (@option_group_id_pt, {foreach from=$locales item=locale}'Phone',{/foreach}         1,     'Phone',     1),
    (@option_group_id_pt, {foreach from=$locales item=locale}'Mobile',{/foreach}        2,     'Mobile',    2),
    (@option_group_id_pt, {foreach from=$locales item=locale}'Fax',{/foreach}           3,     'Fax',       3),
    (@option_group_id_pt, {foreach from=$locales item=locale}'Pager',{/foreach}         4,     'Pager',     4),
    (@option_group_id_pt, {foreach from=$locales item=locale}'Voicemail',{/foreach}     5,     'Voicemail', 5);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,     label,       value, name,        weight) VALUES
    (@option_group_id_pt, 'Phone' ,    1,     'Phone',     1),
    (@option_group_id_pt, 'Mobile',    2,     'Mobile',    2),
    (@option_group_id_pt, 'Fax'   ,    3,     'Fax',       3),
    (@option_group_id_pt, 'Pager' ,    4,     'Pager',     4),
    (@option_group_id_pt, 'Voicemail', 5,     'Voicemail', 5);
{/if}

ALTER TABLE `civicrm_phone`         ADD `phone_type_id` int(10) unsigned NULL DEFAULT NULL AFTER phone_type;
ALTER TABLE `civicrm_mapping_field` ADD `phone_type_id` int(10) unsigned NULL DEFAULT NULL AFTER phone_type;
ALTER TABLE `civicrm_uf_field`      ADD `phone_type_id` int(10) unsigned NULL DEFAULT NULL AFTER phone_type;

SELECT @phonevalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'phone_type' && civicrm_option_value.name = 'Phone';
UPDATE civicrm_phone         SET phone_type_id = @phonevalue WHERE civicrm_phone.phone_type         = 'Phone';
UPDATE civicrm_mapping_field SET phone_type_id = @phonevalue WHERE civicrm_mapping_field.phone_type = 'Phone';
UPDATE civicrm_uf_field      SET phone_type_id = @phonevalue WHERE civicrm_uf_field.phone_type      = 'Phone';

SELECT @phonevalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'phone_type' && civicrm_option_value.name = 'Mobile';
UPDATE civicrm_phone         SET phone_type_id = @phonevalue WHERE civicrm_phone.phone_type         = 'Mobile';
UPDATE civicrm_mapping_field SET phone_type_id = @phonevalue WHERE civicrm_mapping_field.phone_type = 'Mobile';
UPDATE civicrm_uf_field      SET phone_type_id = @phonevalue WHERE civicrm_uf_field.phone_type      = 'Mobile';

SELECT @phonevalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'phone_type' && civicrm_option_value.name = 'Fax';
UPDATE civicrm_phone         SET phone_type_id = @phonevalue WHERE civicrm_phone.phone_type         = 'Fax';
UPDATE civicrm_mapping_field SET phone_type_id = @phonevalue WHERE civicrm_mapping_field.phone_type = 'Fax';
UPDATE civicrm_uf_field      SET phone_type_id = @phonevalue WHERE civicrm_uf_field.phone_type      = 'Fax';

SELECT @phonevalue := value FROM civicrm_option_value, civicrm_option_group 
	WHERE civicrm_option_group.name = 'phone_type' && civicrm_option_value.name = 'Pager';
UPDATE civicrm_phone         SET phone_type_id = @phonevalue WHERE civicrm_phone.phone_type         = 'Pager';
UPDATE civicrm_mapping_field SET phone_type_id = @phonevalue WHERE civicrm_mapping_field.phone_type = 'Pager';
UPDATE civicrm_uf_field      SET phone_type_id = @phonevalue WHERE civicrm_uf_field.phone_type      = 'Pager';

ALTER TABLE `civicrm_phone`         DROP `phone_type`;
ALTER TABLE `civicrm_mapping_field` DROP `phone_type`;
ALTER TABLE `civicrm_uf_field`      DROP `phone_type`;

-- custom Group table 
ALTER TABLE civicrm_custom_group
  ADD  min_multiple int unsigned   DEFAULT 0 COMMENT 'minimum number of multiple records (typically 0?)',
  ADD  max_multiple int unsigned   DEFAULT 0 COMMENT 'maximum number of multiple records, if 0 - no max';

ALTER TABLE civicrm_custom_field
  ADD text_length int unsigned    COMMENT 'field length if alphanumeric' AFTER options_per_line;

-- need to add update statement for site preference options to enable 
-- preferences for contact_type / groups / tags, CRM-2794

SELECT @option_group_id_aso  := max(id) from civicrm_option_group where name = 'advanced_search_options';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,      {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,          weight) VALUES
    (@option_group_id_aso, {foreach from=$locales item=locale}'Contact Type',{/foreach}  16,    'contactType', 16),
    (@option_group_id_aso, {foreach from=$locales item=locale}'Groups',{/foreach}        17,    'groups',      17),
    (@option_group_id_aso, {foreach from=$locales item=locale}'Tags',{/foreach}          18,    'tags',        18);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,      label,          value, name,          weight) VALUES
    (@option_group_id_aso, 'Contact Type', 16,    'contactType', 16),
    (@option_group_id_aso, 'Groups',       17,    'groups',      17),
    (@option_group_id_aso, 'Tags',         18,    'tags',        18);
{/if}


UPDATE civicrm_preferences SET advanced_search_options = CONCAT(advanced_search_options, '161718');

ALTER TABLE civicrm_preferences
  ADD mailing_backend text COMMENT 'Smtp Backend configuration.';


-- /**
--  * add new mailing fields
--  * CRM-3599 (created_id, scheduled_id)
--  * CRM-3598 (search_id, search_args)
-- **/

ALTER TABLE civicrm_mailing
  ADD override_verp tinyint   DEFAULT 0 AFTER msg_template_id,
  ADD created_id int unsigned NULL DEFAULT NULL AFTER override_verp,
  ADD scheduled_id int unsigned NULL DEFAULT NULL AFTER created_id,
  ADD is_archived tinyint   DEFAULT 0 COMMENT 'Is this mailing archived?', 	
  ADD CONSTRAINT FK_civicrm_mailing_created_id FOREIGN KEY (created_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE,
  ADD CONSTRAINT FK_civicrm_mailing_scheduled_id FOREIGN KEY (scheduled_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE;

ALTER TABLE civicrm_mailing_group
  ADD search_id int AFTER entity_id,
  ADD search_args text AFTER search_id,
  MODIFY `group_type` enum('Include','Exclude','Base') NULL DEFAULT NULL;


-- CRM-3609 (used IGNORE as 2.1 post beta5 should have this already)
 
INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES (5217, 1020, "BRU", "Brussels");

-- ======== CiviCase Related Upgrade ==========
-- Insert the CiviCase Component
INSERT INTO `civicrm_component` ( `name`, `namespace`) VALUES ( 'CiviCase','CRM_Case' );
SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';

-- CRM-3667 case mapping
SELECT @option_group_id_mt := max(id) from civicrm_option_group where name = 'mapping_type';

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,     {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,          weight, is_reserved) VALUES
    (@option_group_id_mt, {foreach from=$locales item=locale}'Export Case',{/foreach}   12,    'Export Case', 12,     1);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,     label,         value, name,          weight, is_reserved) VALUES
    (@option_group_id_mt, 'Export Case', 12,    'Export Case', 12,     1);
{/if}

-- * Case Status - Set names for Open and Closed and add Urgent status
SELECT @csgId := max(id) FROM civicrm_option_group WHERE name = 'case_status';

{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_option_value SET name = 'Open'   WHERE option_group_id = @csgId AND label_{$locale} = 'Ongoing';
    UPDATE civicrm_option_value SET name = 'Closed' WHERE option_group_id = @csgId AND label_{$locale} = 'Resolved';
  {/foreach}
  INSERT INTO civicrm_option_value
    (option_group_id, {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,     weight, is_reserved) VALUES
    (@csgId,          {foreach from=$locales item=locale}'Urgent',{/foreach}        3,     'Urgent', 3,      1);
{else}
  UPDATE civicrm_option_value SET name = 'Open'   WHERE option_group_id = @csgId AND label = 'Ongoing';
  UPDATE civicrm_option_value SET name = 'Closed' WHERE option_group_id = @csgId AND label = 'Resolved';
  INSERT INTO civicrm_option_value
    (option_group_id, label,    value, name,     weight, is_reserved) VALUES
    (@csgId,          'Urgent', 3,     'Urgent', 3,      1);
{/if}

-- Relationship Types for cases
INSERT INTO civicrm_relationship_type( name_a_b, name_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Case Coordinator is', 'Case Coordinator', 'Case Coordinator', 'Individual', 'Individual', 0 );

INSERT INTO civicrm_relationship_type( name_a_b, name_b_a, description, contact_type_a, contact_type_b, is_reserved )
    VALUES( 'Supervised by', 'Supervisor', 'Immediate workplace supervisor', 'Individual', 'Individual', 0 );

-- Activity Types for cases
SELECT @option_group_id_activity_type        := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;

{if $multilingual}
  INSERT INTO civicrm_option_value
    (option_group_id,                {foreach from=$locales item=locale}label_{$locale},{/foreach}      value,                           name,                 weight,                          component_id) VALUES
    (@option_group_id_activity_type, {foreach from=$locales item=locale}'Open Case',{/foreach}          (SELECT @max_val := @max_val+1), 'Open Case',          (SELECT @max_val := @max_val+1), @caseCompId),
    (@option_group_id_activity_type, {foreach from=$locales item=locale}'Follow up',{/foreach}          (SELECT @max_val := @max_val+1), 'Follow up',          (SELECT @max_val := @max_val+1), @caseCompId),
    (@option_group_id_activity_type, {foreach from=$locales item=locale}'Change Case Type',{/foreach}   (SELECT @max_val := @max_val+1), 'Change Case Type',   (SELECT @max_val := @max_val+1), @caseCompId),  
    (@option_group_id_activity_type, {foreach from=$locales item=locale}'Change Case Status',{/foreach} (SELECT @max_val := @max_val+1), 'Change Case Status', (SELECT @max_val := @max_val+1), @caseCompId),  
    (@option_group_id_activity_type, {foreach from=$locales item=locale}'Close Case',{/foreach}         (SELECT @max_val := @max_val+1), 'Close Case',         (SELECT @max_val := @max_val+1), @caseCompId);
{else}
  INSERT INTO civicrm_option_value
    (option_group_id,                label,                value,                           name,                 weight,                          component_id) VALUES
    (@option_group_id_activity_type, 'Open Case',          (SELECT @max_val := @max_val+1), 'Open Case',          (SELECT @max_val := @max_val+1), @caseCompId),
    (@option_group_id_activity_type, 'Follow up',          (SELECT @max_val := @max_val+1), 'Follow up',          (SELECT @max_val := @max_val+1), @caseCompId),
    (@option_group_id_activity_type, 'Change Case Type',   (SELECT @max_val := @max_val+1), 'Change Case Type',   (SELECT @max_val := @max_val+1), @caseCompId),
    (@option_group_id_activity_type, 'Change Case Status', (SELECT @max_val := @max_val+1), 'Change Case Status', (SELECT @max_val := @max_val+1), @caseCompId),  
    (@option_group_id_activity_type, 'Close Case',         (SELECT @max_val := @max_val+1), 'Close Case',         (SELECT @max_val := @max_val+1), @caseCompId);
{/if}

-- Encounter Medium Option Values for Case Activities
{if $multilingual}
  INSERT INTO civicrm_option_group
    (name, {foreach from=$locales item=locale}label_{$locale}, description_{$locale},{/foreach} is_reserved, is_active) 
    VALUES
    ('encounter_medium', {foreach from=$locales item=locale}'Encounter Medium', 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)',{/foreach} 1, 1 );
  SELECT @option_group_id_medium := max(id) FROM civicrm_option_group WHERE name = 'encounter_medium';
  INSERT INTO civicrm_option_value
    (option_group_id,         {foreach from=$locales item=locale}label_{$locale},{/foreach} value, name,          is_default, weight, is_reserved) VALUES
    (@option_group_id_medium, {foreach from=$locales item=locale}'In Person',{/foreach}     1,     'in_person',   0,          1,      1),
    (@option_group_id_medium, {foreach from=$locales item=locale}'Phone',{/foreach}         2,     'phone',       1,          2,      1),
    (@option_group_id_medium, {foreach from=$locales item=locale}'Email',{/foreach}         3,     'email',       0,          3,      1),
    (@option_group_id_medium, {foreach from=$locales item=locale}'Fax',{/foreach}           4,     'fax',         0,          4,      1),
    (@option_group_id_medium, {foreach from=$locales item=locale}'Letter Mail',{/foreach}   5,     'letter_mail', 0,          5,      1);
{else}
  INSERT INTO civicrm_option_group
    (name, label, description, is_reserved, is_active ) 
    VALUES
    ('encounter_medium', 'Encounter Medium', 'Encounter medium for case activities (e.g. In Person, By Phone, etc.)', 1, 1);
  SELECT @option_group_id_medium := max(id) FROM civicrm_option_group WHERE name = 'encounter_medium';
  INSERT INTO civicrm_option_value
    (option_group_id,         label,         value, name,          is_default, weight, is_reserved) VALUES
    (@option_group_id_medium, 'In Person',   1,     'in_person',   0,          1,      1),
    (@option_group_id_medium, 'Phone',       2,     'phone',       1,          2,      1),
    (@option_group_id_medium, 'Email',       3,     'email',       0,          3,      1),
    (@option_group_id_medium, 'Fax',         4,     'fax',         0,          4,      1),
    (@option_group_id_medium, 'Letter Mail', 5,     'letter_mail', 0,          5,      1);
{/if}

-- CRM-3573 
-- added column case_id in civicrm_relationship table.
-- added columns medium, is_auto, relationship_id fileds in civicrm_activity.
-- added value 'Case' in civicrm_custom_group.

ALTER TABLE `civicrm_relationship`
  ADD `case_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_case' AFTER is_permission_b_a,
  ADD CONSTRAINT FK_civicrm_case_id FOREIGN KEY (case_id) REFERENCES civicrm_case(id) ON DELETE CASCADE;

ALTER TABLE `civicrm_case`
 ADD `is_deleted` tinyint   DEFAULT 0;

ALTER TABLE `civicrm_activity`
  ADD `medium_id` int(10) unsigned default NULL COMMENT 'Activity Medium, Implicit FK to civicrm_option_value where option_group = activity_medium.',
  ADD `is_auto` tinyint(4) default '0' COMMENT 'if true - activity is auto populated while case review',
  ADD `relationship_id` int(10) unsigned default NULL COMMENT 'FK to Relationship ID',
  ADD `is_current_revision` tinyint   DEFAULT 1 ,
  ADD `original_id` int unsigned    COMMENT 'Activity ID of the first activity record in versioning chain.',
  ADD `is_deleted` tinyint   DEFAULT 0,
  ADD CONSTRAINT FK_civicrm_activity_original_id FOREIGN KEY (original_id) REFERENCES civicrm_activity(id) ON DELETE CASCADE,  
  ADD CONSTRAINT FK_civicrm_relationship_id FOREIGN KEY (relationship_id) REFERENCES civicrm_relationship(id) ON DELETE SET NULL;

ALTER TABLE `civicrm_custom_group`
  MODIFY `extends` enum('Contact','Individual','Household','Organization','Location','Address','Contribution','Activity','Relationship','Group','Membership','Participant','Event','Grant','Pledge','Case') collate utf8_unicode_ci default 'Contact' COMMENT 'Type of object this group extends (can add other options later e.g. contact_address, etc.).';

-- schema change CRM-3337
ALTER TABLE `civicrm_custom_group` CHANGE `extends_entity_column_name` `extends_entity_column_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'reference to option value';

-- added new column 'skipBreadcrumb' to civicrm_menu. CRM-2699.

ALTER TABLE `civicrm_menu`
  ADD `skipBreadcrumb` tinyint(4) COMMENT 'skip this url being exposed to breadcrumb';

-- CRM-3709  
CREATE INDEX index_option_group_id_name ON civicrm_option_value( `option_group_id` , `name` );

-- fix constraint
ALTER TABLE `civicrm_group_nesting`
    DROP FOREIGN KEY `FK_civicrm_group_nesting_child_group_id`;
ALTER TABLE `civicrm_group_nesting`
    ADD CONSTRAINT `FK_civicrm_group_nesting_child_group_id` FOREIGN KEY (`child_group_id`) REFERENCES `civicrm_group` (`id`) ON DELETE CASCADE;

ALTER TABLE `civicrm_group`
 ADD `is_hidden` tinyint   DEFAULT 0 COMMENT 'Is this group hidden?';

-- New field for CRM-3494 (billing name and address for contributions)
ALTER TABLE `civicrm_contribution`
  ADD `address_id` int(10) unsigned default NULL COMMENT 'Conditional foreign key to civicrm_address.id. We insert an address record for each contribution when we have associated billing name and address data.',
  ADD CONSTRAINT FK_civicrm_contribution_address_id FOREIGN KEY (address_id) REFERENCES civicrm_address(id) ON DELETE SET NULL;

-- Removing solicitor_id as per CRM-3917
INSERT INTO civicrm_contribution_soft (contribution_id, contact_id, amount) 
SELECT id, solicitor_id, total_amount FROM civicrm_contribution
WHERE solicitor_id IS NOT NULL;

ALTER TABLE `civicrm_contribution` 
-- Added check_number as per CRM-3923
  ADD `check_number` varchar(255) collate utf8_unicode_ci default NULL,
  DROP FOREIGN KEY `FK_civicrm_contribution_solicitor_id`;

DROP INDEX FK_civicrm_contribution_solicitor_id ON civicrm_contribution;
ALTER TABLE civicrm_contribution DROP solicitor_id;

-- Make sure is_billing flag is true for all address records where location type is Billing
-- Using hard-coded location_type_id since we don't have a non-translatable name for location types
UPDATE `civicrm_address`
SET `is_billing` = 1
WHERE `location_type_id` = 5;

-- civicrm_note constraint fix
ALTER TABLE civicrm_note 
    DROP FOREIGN KEY `FK_civicrm_note_contact_id`;
ALTER TABLE `civicrm_note`
    ADD CONSTRAINT `FK_civicrm_note_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL;

-- CRM-3860
ALTER TABLE civicrm_contact
    ADD api_key varchar(32);
CREATE INDEX index_api_key ON civicrm_contact(api_key);

-- CRM-3863
SELECT @piOpt := id from civicrm_option_group where name = 'payment_instrument';
UPDATE civicrm_option_value SET is_reserved = 1 WHERE option_group_id = @piOpt AND name = 'Credit Card';

-- CRM-3851: migrate civicrm_domain.email_domain and .email_return_path to civicrm_mail_settings
SELECT email_domain, email_return_path FROM civicrm_domain LIMIT 1 INTO @domain, @return_path;


CREATE TABLE `civicrm_mail_settings` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT 'primary key',
  `name` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'name of this group of settings',
  `is_default` tinyint(4) default NULL COMMENT 'whether this is the default set of settings for this domain',
  `domain` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'email address domain (the part after @)',
  `localpart` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'optional local part (like civimail+ for addresses like civimail+s.1.2@example.com)',
  `return_path` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'contents of the Return-Path header',
  `protocol` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'name of the protocol to use for polling (like IMAP, POP3 or Maildir)',
  `server` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'server to use when polling',
  `port` int(10) unsigned default NULL COMMENT 'port to use when polling',
  `username` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'username to use when polling',
  `password` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'password to use when polling',
  `is_ssl` tinyint(4) default NULL COMMENT 'whether to use SSL or not',
  `source` varchar(255) collate utf8_unicode_ci default NULL COMMENT 'folder to poll from when using IMAP, path to poll from when using Maildir, etc.',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO civicrm_mail_settings (name, is_default, domain, return_path) VALUES ('default', true, @domain, @return_path);
ALTER TABLE civicrm_domain DROP email_domain;
ALTER TABLE civicrm_domain DROP email_return_path;

-- CRM-4258, needs to be done before we add a unique index
delete t1 from civicrm_entity_tag t1 inner join civicrm_entity_tag t2 where t1.tag_id = t2.tag_id AND t1.contact_id = t2.contact_id AND t1.id > t2.id;

-- CRM-3696
ALTER TABLE civicrm_entity_tag
  ADD UNIQUE UI_contact_id_tag_id ( contact_id, tag_id );


SELECT @option_group_id_ps := id from civicrm_option_group where name = 'participant_status';
UPDATE civicrm_option_value
   SET visibility_id = CASE name
                              WHEN 'Registered' THEN 1
                              ELSE 2
                          END
   WHERE option_group_id = @option_group_id_ps;

-- CRM-3487
ALTER TABLE civicrm_uf_group DROP collapse_display;


-- FIXED FOR CRM-3772
-- upgrading civicrm_country table for region id by taking reference of civicrm_worldregion

-- Europe and Central Asia

SELECT @region_id   := max(id) from civicrm_worldregion where name = "Europe and Central Asia";

UPDATE `civicrm_country` SET `region_id` = @region_id  WHERE `civicrm_country`.`iso_code` IN( "AL","AD","AQ","AT","AZ","BY","BE","BA","BV","BG","HR","CY","CZ","DK","EE","FO","FI","FR","TF","DE","GI","GR","VA","HU","IS","IE","IT","KZ","KG","LV","LI","LT","LU","MK","MT","MD","MC","NL","NO","PL","PT","RO","RU","SM","SK","SI","ES","SJ","SE","CH","TR","TM","UA","GB","UZ","CS","AX","RS","ME" ) AND `civicrm_country`.`region_id` IS null;

-- America South, Central, North and Carribean

SELECT @region_id   := max(id) from civicrm_worldregion where name = "America South, Central, North and Carribean";

UPDATE `civicrm_country` SET `region_id` = @region_id  WHERE `civicrm_country`.`iso_code` IN( "AS","AI","AG","AR","AW","BZ","BM","BO","BR","VG","CA","CL","CO","CR","CU","DM","DO","EC","SV","FK","GF","GL","GD","GP","GT","GY","HT","HN","JM","MQ","MX","MS","AN","NI","MP","PY","PE","PR","KN","LC","PM","VC","GS","SR","BS","TT","TC","UM","US","UY","VE","VI" ) AND `civicrm_country`.`region_id` IS null;

-- Middle East and North Africa

SELECT @region_id   := max(id) from civicrm_worldregion where name = "Middle East and North Africa";

UPDATE `civicrm_country` SET `region_id` = @region_id  WHERE `civicrm_country`.`iso_code` IN( "DZ","BH","EG","IR","IQ","IL","JO","KW","LB","LY","MA","OM","PS","QA","SA","SY","TN","AE","EH","YE" ) AND `civicrm_country`.`region_id` IS null;

-- Asia-Pacific

SELECT @region_id   := max(id) from civicrm_worldregion where name = "Asia-Pacific";

UPDATE `civicrm_country` SET `region_id` = @region_id  WHERE `civicrm_country`.`iso_code` IN( "AF","AM","AU","BD","BB","BT","IO","BN","MM","KH","CN","CX","CC","CK","TL","FJ","PF","GE","GU","HM","HK","IN","ID","JP","KI","KP","KR","LA","MO","MY","MV","MH","FM","MN","NR","NP","NC","NZ","NU","NF","PK","PW","PG","PH","PN","WS","SG","SB","LK","TW","TJ","TH","TK","TO","TV","VU","VN","WF" ) AND `civicrm_country`.`region_id` IS null;

-- Africa West, East, Central and Southern

SELECT @region_id   := max(id) from civicrm_worldregion where name = "Africa West, East, Central and Southern";

UPDATE `civicrm_country` SET `region_id` = @region_id  WHERE `civicrm_country`.`iso_code` IN( "AO","BJ","BW","BF","BI","CM","CV","KY","CF","TD","KM","CD","CG","CI","DJ","GQ","ER","ET","GA","GH","GN","GW","KE","LS","LR","MG","MW","ML","MR","MU","YT","MZ","NA","NE","NG","PA","RW","RE","SH","SN","SC","SL","SO","ZA","SD","SZ","ST","TZ","GM","TG","UG","ZM","ZW" ) AND `civicrm_country`.`region_id` IS null;

-- unassigned

SELECT @region_id   := max(id) from civicrm_worldregion where name = "unassigned";

UPDATE `civicrm_country` SET `region_id` = @region_id WHERE `civicrm_country`.`iso_code` IN( "JE","GG","IM" ) AND `civicrm_country`.`region_id` IS null;


-- update minute increment to 1 in activitydatetime in preferences_date
UPDATE `civicrm_preferences_date` SET `minute_increment` = 1 WHERE `name`  = 'activityDatetime';

-- CRM-3730
ALTER TABLE civicrm_price_field ALTER active_on SET DEFAULT NULL;
ALTER TABLE civicrm_price_field ALTER expire_on SET DEFAULT NULL;

-- ******************************************************
-- END OF THE UPGRADE
