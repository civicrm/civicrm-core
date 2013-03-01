-- CRM-5536, CRM-5535

INSERT INTO civicrm_payment_processor_type 
( name, title, description, is_active, is_default, user_name_label, password_label, signature_label, subject_label, class_name, url_site_default, url_api_default, url_recur_default, url_button_default, url_site_test_default, url_api_test_default, url_recur_test_default, url_button_test_default, billing_mode, is_recur, payment_type) 
VALUES
( 'PayflowPro', '{ts escape="sql"}PayflowPro{/ts}', NULL, 1, 0, 'Vendor ID', 'Password', 'Partner (merchant)', 'User', 'Payment_PayflowPro', 'https://Payflowpro.paypal.com', NULL, NULL, NULL, 'https://pilot-Payflowpro.paypal.com', NULL, NULL, NULL, 1, 0, 1),
( 'FirstData', '{ts escape="sql"}FirstData (aka linkpoint){/ts}', '{ts escape="sql"}FirstData (aka linkpoint){/ts}', 1, 0, 'Store Name', 'Certificate Path', NULL, NULL, 'Payment_FirstData', 'https://secure.linkpt.net', NULL, NULL, NULL, 'https://staging.linkpt.net', NULL, NULL, NULL, 1, NULL, 1);

-- CRM-5461 
    SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
    SELECT @activity_type_max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id = @option_group_id_act;
    SELECT @activity_type_max_wt  := MAX(ROUND(val.weight)) FROM civicrm_option_value val where val.option_group_id = @option_group_id_act;

    INSERT INTO civicrm_option_value
        ( `option_group_id`,{localize field='label'}`label`{/localize},`value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`)
    VALUES
        ( @option_group_id_act, {localize}'Print PDF Letter'{/localize}, (SELECT @activity_type_max_val := @activity_type_max_val + 1 ), 'Print PDF Letter', NULL, 1, NULL, (SELECT @activity_type_max_wt := @activity_type_max_wt + 1 ), {localize}'Print PDF Letter.'{/localize}, 0, 1, 1, NULL, NULL, NULL);

-- CRM-5344
    ALTER TABLE civicrm_uf_group
    MODIFY notify text;

-- CRM-5598

SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';

SELECT @atOpt_max_val := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id = @option_group_id_activity_type;

SELECT @atOpt_max_wt  := MAX(ROUND(val.weight)) FROM civicrm_option_value val where val.option_group_id = @option_group_id_activity_type;

SELECT @caseCompId    := max(id) FROM civicrm_component where name = 'CiviCase';

INSERT INTO 
   civicrm_option_value(`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
VALUES(@option_group_id_activity_type, {localize}'Merge Case'{/localize}, (SELECT @atOpt_max_val := @atOpt_max_val+1), 'Merge Case', NULL, 0, NULL, (SELECT @atOpt_max_wt := @atOpt_max_wt + 1 ), 0, 1, 1, @caseCompId, NULL ), 
      (@option_group_id_activity_type, {localize}'Reassigned Case'{/localize}, (SELECT @atOpt_max_val := @atOpt_max_val+1), 'Reassigned Case', NULL, 0, NULL, (SELECT @atOpt_max_wt := @atOpt_max_wt + 1 ), 0, 1, 1, @caseCompId, NULL ),
      (@option_group_id_activity_type, {localize}'Link Cases'{/localize}, (SELECT @atOpt_max_val := @atOpt_max_val+1), 'Link Cases', NULL, 0, NULL, (SELECT @atOpt_max_wt := @atOpt_max_wt + 1 ), 0, 1, 1, @caseCompId, NULL );
      

-- CRM-5752
    UPDATE civicrm_option_value val 
        LEFT JOIN civicrm_option_group gr ON ( gr.id = val.option_group_id ) 
        SET val.is_reserved = 1
        WHERE gr.name = 'contribution_status' AND val.name IN ( 'Completed', 'Pending', 'Cancelled', 'Failed', 'In Progress', 'Overdue' );

-- CRM-5831
    ALTER TABLE civicrm_email 
    	ADD `signature_text` text COLLATE utf8_unicode_ci COMMENT 'Text formatted signature for the email.',
	ADD `signature_html` text COLLATE utf8_unicode_ci COMMENT 'HTML formatted signature for the email.';

-- CRM-5787
   UPDATE civicrm_option_value val
       	INNER JOIN civicrm_option_group gr ON ( gr.id = val.option_group_id )   
	SET val.grouping = 'Opened' 
	WHERE gr.name = 'case_status' AND val.name IN ( 'Open', 'Urgent' );
   
   UPDATE civicrm_option_value val
       	INNER JOIN civicrm_option_group gr ON ( gr.id = val.option_group_id )  	 
	SET val.grouping = 'Closed'  
	WHERE gr.name = 'case_status' AND val.name = 'Closed';

   SELECT @domain_id := min(id) FROM civicrm_domain;
   SELECT @nav_case    := id FROM civicrm_navigation WHERE name = 'CiviCase';
   SELECT @nav_case_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_case;

   INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
   VALUES
	( @domain_id, 'civicrm/admin/options/case_status&group=case_status&reset=1', '{ts escape="sql"}Case Statuses{/ts}','Case Statuses',  'administer CiviCase', NULL, @nav_case, '1', NULL, @nav_case_weight+1 );

-- CRM-5766
   ALTER TABLE civicrm_price_field
   ADD `visibility_id` int(10) unsigned default 1 COMMENT 'Implicit FK to civicrm_option_group with name = visibility.';

-- CRM-5612
   ALTER TABLE civicrm_cache
   MODIFY path varchar(255) COMMENT 'Unique path name for cache element';
   
-- CRM-5874
   ALTER TABLE civicrm_uf_group
   ADD `is_proximity_search` tinyint(4) unsigned default 0 COMMENT 'Should proximity search be included in profile search form?';

-- CRM-5724

   ALTER TABLE civicrm_price_field
   ADD `count` int(10) unsigned default NULL COMMENT 'Participant count for field.';

   ALTER TABLE civicrm_line_item
   ADD `participant_count` int(10) unsigned default NULL COMMENT 'Number of Participants Per field.';
   
-- CRM-5970
-- civicrm_entity_financial_trxn
   CREATE TABLE `civicrm_entity_financial_trxn` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `entity_table` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `entity_id` int(10) unsigned NOT NULL,
  `financial_trxn_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(20,2) NOT NULL COMMENT 'allocated amount of transaction to this entity',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_entity_financial_trxn_financial_trxn_id` (`financial_trxn_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- civicrm_financial_account
   CREATE TABLE `civicrm_financial_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `account_type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Constraints for table `civicrm_entity_financial_trxn`
   ALTER TABLE `civicrm_entity_financial_trxn`
     ADD CONSTRAINT `FK_civicrm_entity_financial_trxn_financial_trxn_id` FOREIGN KEY (`financial_trxn_id`) REFERENCES `civicrm_financial_trxn` (`id`) ON DELETE SET NULL;
  
-- Insert financial_trxn_id.contribution_id values into new rows in civicrm_entity_financial_trxn to preserve existing linkages
    INSERT INTO civicrm_entity_financial_trxn (financial_trxn_id, amount, entity_id, entity_table)
    SELECT id, total_amount, contribution_id, 'civicrm_contribution'
    FROM   civicrm_financial_trxn ft
    ON DUPLICATE KEY UPDATE civicrm_entity_financial_trxn.entity_id = ft.contribution_id;

-- ALTER civicrm_financial_trxn
   ALTER TABLE `civicrm_financial_trxn` 
       DROP FOREIGN KEY `FK_civicrm_financial_trxn_contribution_id`  ;
   ALTER TABLE `civicrm_financial_trxn` 
       DROP `contribution_id`;
   ALTER TABLE `civicrm_financial_trxn`
       ADD `from_account_id` INT( 10 ) unsigned NULL,
       ADD `to_account_id` INT( 10 ) unsigned NULL;
   ALTER TABLE `civicrm_financial_trxn`
       ADD FOREIGN KEY `FK_civicrm_financial_trxn_from_account_id` ( `from_account_id` ) REFERENCES `civicrm_financial_account`  (`id`) ,      
       ADD FOREIGN KEY `FK_civicrm_financial_trxn_to_account_id` (`to_account_id`) REFERENCES `civicrm_financial_account`(`id`);
   
-- INSERT civicrm_option_group
   INSERT INTO 
   `civicrm_option_group` (`name`, {localize field='description'}`description`{/localize} , `is_reserved`, `is_active`) 
VALUES 			  
    ('account_type',{localize}'{ts escape="sql"}Account type{/ts}'{/localize}, 0, 1);
   
-- INSERT Account types
   SELECT @option_group_id_accTp          := max(id) from civicrm_option_group where name = 'account_type';
   INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
   VALUES
   (@option_group_id_accTp, {localize}'{ts escape="sql"}Asset{/ts}'{/localize}, 1, 'Asset',  NULL, 0, NULL, 1,{localize} NULL{/localize} , 0, 0, 1, NULL, NULL),
   (@option_group_id_accTp,{localize}'{ts escape="sql"}Liability{/ts}'{/localize}, 2, 'Liability',  NULL, 0, NULL, 1,{localize} NULL{/localize}  , 0, 0, 1, NULL, NULL),
   (@option_group_id_accTp,{localize}'{ts escape="sql"}Income{/ts}'{/localize}, 3, 'Income',  NULL, 0, NULL, 1,{localize} NULL {/localize}, 0, 0, 1, NULL, NULL),
   (@option_group_id_accTp, {localize}'{ts escape="sql"}Expense{/ts}'{/localize}, 4, 'Expense',  NULL, 0, NULL, 1,{localize}  NULL {/localize}, 0, 0, 1, NULL, NULL);

--  CRM-5883

--  add table civicrm_website 
    CREATE TABLE civicrm_website (
        id int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique Website Id.',
        contact_id int unsigned NULL DEFAULT NULL COMMENT 'FK To Contact ID.',
        url varchar(128) NULL DEFAULT NULL COMMENT 'Website.',
        website_type_id int unsigned NULL DEFAULT NULL COMMENT 'Which Website type does this website belong to.',
      	PRIMARY KEY ( id ),
	INDEX UI_website_type_id( website_type_id ),
	CONSTRAINT FK_civicrm_website_contact_id FOREIGN KEY (contact_id) REFERENCES civicrm_contact(id) ON DELETE CASCADE
    )  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;
    
--  insert home_URL and image_URL for already exists contacts
    INSERT INTO civicrm_website ( contact_id, url, website_type_id ) SELECT cc.id, cc.home_URL, 1 FROM civicrm_contact cc WHERE cc.home_URL IS NOT NULL ;

--  drop columns home_URL
    ALTER TABLE civicrm_contact DROP home_URL;

--  add option group website_type
    INSERT INTO civicrm_option_group
        (name, {localize field='description'}description{/localize}, is_reserved, is_active)
    VALUES 
        ('website_type', {localize}'Website Type'{/localize} , 0, 1),
        ('tag_used_for', {localize}'Tag Used For'{/localize}, 0, 1);
    SELECT @option_group_id_website := max(id) FROM civicrm_option_group WHERE name = 'website_type' ;
    SELECT @option_group_id_tuf := max(id) FROM civicrm_option_group WHERE name = 'tag_used_for' ;
    
    INSERT INTO civicrm_option_value
    	(option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize} , is_optgroup, is_reserved, is_active, component_id, visibility_id) 
    VALUES
       (@option_group_id_website, {localize}'Home' {/localize},    1, 'Home',     NULL, 0, NULL, 1,{localize} NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_website, {localize}'Work'{/localize},     2, 'Work',     NULL, 0, NULL, 2, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_website, {localize}'Facebook'{/localize}, 3, 'Facebook', NULL, 0, NULL, 3, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_website, {localize}'Twitter'{/localize},  4, 'Twitter',  NULL, 0, NULL, 4,{localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_website, {localize}'MySpace'{/localize},  5, 'MySpace',  NULL, 0, NULL, 5, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_tuf, {localize}'Contacts'{/localize}, 'civicrm_contact', 'Contacts', NULL, 0, NULL, 1,{localize}NULL{/localize}, 0, 0, 1, NULL, NULL),
       (@option_group_id_tuf, {localize}'Activities'{/localize}, 'civicrm_activity', 'Activities',  NULL, 0, NULL, 2,{localize}NULL{/localize}, 0, 0, 1, NULL, NULL),	
       (@option_group_id_tuf, {localize}'Cases'{/localize}, 'civicrm_case', 'Cases', NULL, 0, NULL, 3,{localize}NULL{/localize}, 0, 0, 1, NULL, NULL);
       
--  CRM-5962

--  add columns entity_table , entity_id in civicrm_entity_tag
    ALTER TABLE civicrm_entity_tag 
    ADD entity_table varchar(64) NULL DEFAULT NULL COMMENT 'physical tablename for entity being joined to file, e.g. civicrm_contact' AFTER id,
    DROP FOREIGN KEY FK_civicrm_entity_tag_contact_id,
    DROP INDEX UI_contact_id_tag_id, CHANGE contact_id entity_id int unsigned NOT NULL COMMENT 'FK to entity table specified in entity_table column.',
    ADD INDEX index_entity (entity_table, entity_id) ;

--  entity_table field for exists records is civicrm_contact
    UPDATE civicrm_entity_tag 
    SET entity_table ='civicrm_contact' ;

--  add is_reserved, is_hidden, used_for in civicrm_tag
    ALTER TABLE civicrm_tag 
    ADD is_reserved tinyint DEFAULT 0, 
    ADD is_hidden tinyint DEFAULT 0, 
    ADD used_for varchar(64) NULL DEFAULT NULL;

   UPDATE civicrm_tag
   SET used_for ='civicrm_contact';

-- Add new activity type Change Case Tag
 SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
 SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
 SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;
 SELECT @caseCompId := id FROM `civicrm_component` where `name` like 'CiviCase';
 INSERT INTO civicrm_option_value
    	(option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight,is_reserved, is_active, component_id )
VALUES 
       ( @option_group_id_activity_type,{localize}'Change Case Tags'{/localize},(SELECT @max_val := @max_val+1),'Change Case Tags','NULL',0,0,(SELECT @max_wt := @max_wt+1),1,1,@caseCompId);

   {include file='../CRM/Upgrade/3.2.alpha1.msg_template/civicrm_msg_template.tpl'}

-- CRM-6024
   UPDATE civicrm_participant_status_type
   	  SET is_counted = 0
   	  WHERE name = 'Pending from incomplete transaction';

-- CRM-6004
ALTER TABLE civicrm_uf_field
  ADD help_pre text COLLATE utf8_unicode_ci COMMENT 'Description and/or help text to display before this field.';

-- CRM-6002
    INSERT INTO `civicrm_state_province`
        (`name`, `abbreviation`, `country_id`)
    VALUES
        ('La Rioja', 'F', 1010 );

-- CRM-6037
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Host';
INSERT INTO civicrm_mailing_bounce_pattern
        (bounce_type_id, pattern)
        VALUES
    (@bounceTypeID, 'not connected');

-- CRM-6045
UPDATE civicrm_payment_processor_type 
SET url_site_test_default = 'https://www.payjunctionlabs.com/quick_link' WHERE name = 'PayJunction';

-- CRM-5803
   SELECT @domain_id := min(id) FROM civicrm_domain;
   SELECT @nav_search    := id FROM civicrm_navigation WHERE name = 'Search...';
   SELECT @nav_max_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_search;
   SELECT @nav_find_pledge_weight := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_search AND has_separator = 1;
   SELECT @pledge_id := id from civicrm_navigation WHERE parent_id = @nav_search AND weight = @nav_find_pledge_weight;
   
   UPDATE civicrm_navigation SET has_separator = NULL WHERE id = @pledge_id LIMIT 1;
   UPDATE civicrm_navigation SET weight =  @nav_max_weight+1 WHERE parent_id = @nav_search AND weight = @nav_max_weight;
   
   INSERT INTO civicrm_navigation
        ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
   VALUES
        ( @domain_id,  'civicrm/activity/search&reset=1', '{ts escape="sql"}Find Activities{/ts}','Find Activities', NULL, 
'', @nav_search, '1', 1, @nav_find_pledge_weight );
  
  SELECT @option_group_id_mt := max(id) from civicrm_option_group where name = 'mapping_type';
  SELECT @max_val            := MAX(ROUND(op.value))   FROM civicrm_option_value op  WHERE op.option_group_id  = @option_group_id_mt;
  SELECT @max_wt             := MAX(ROUND(val.weight)) FROM civicrm_option_value val WHERE val.option_group_id = @option_group_id_mt;

   INSERT INTO civicrm_option_value
   (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`) 
VALUES
   (@option_group_id_mt, {localize}'Export Activities'{/localize},  @max_val+1, 'Export Activity', NULL, 0, 0, @max_wt+1, 0, 1, 1);

-- CRM-6063
   INSERT INTO civicrm_state_province
        (`name`, `abbreviation`, `country_id` )
   VALUES
        ( 'Andorra la Vella', '07', 1005 ),
        ( 'Canillo', '02', 1005 ),
        ( 'Encamp', '03', 1005 ),
        ( 'Escaldes-Engordany', '08', 1005 ),
        ( 'La Massana', '04', 1005 ),
        ( 'Ordino','05', 1005 ),
        ( 'Sant Julia de Loria', '06', 1005 );

-- CRM-5673
ALTER TABLE civicrm_contact ADD is_deleted TINYINT;
ALTER TABLE civicrm_contact ADD INDEX index_is_deleted(is_deleted);

-- CRM-5467
   ALTER TABLE civicrm_contact
   MODIFY image_URL varchar(255) COMMENT 'Optional URL for preferred image (photo, logo, etc.) to display for this contact';

-- CRM-6095
   UPDATE civicrm_navigation SET permission ='access my cases and activities,access all cases and activities', permission_operator='OR' WHERE civicrm_navigation.name= 'Dashboard' AND url='civicrm/case&reset=1';
   UPDATE civicrm_navigation SET permission ='access my cases and activities,access all cases and activities', permission_operator='OR' WHERE civicrm_navigation.name IN ( 'Find Cases','Cases');
   UPDATE civicrm_navigation SET permission ='access all cases and activities' WHERE permission='access CiviCase';
   UPDATE civicrm_navigation SET permission ='access CiviGrant,administer CiviCase,access my cases and activities,access all cases and activities' WHERE civicrm_navigation.name= 'Other';
   UPDATE civicrm_navigation SET permission ='administer CiviCase', permission_operator= NULL WHERE civicrm_navigation.name IN ( 'CiviCase','Case Types', 'Redaction Rules');
   UPDATE civicrm_report_instance SET permission ='access all cases and activities' WHERE permission='access CiviCase';