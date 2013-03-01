--CRM-6455
SELECT @domainID               := MIN(id) FROM civicrm_domain;
SELECT @option_group_id_editor := MAX(id) from civicrm_option_group where name = 'wysiwyg_editor';
SELECT @max_value              := MAX(ROUND(value)) from civicrm_option_value where option_group_id = @option_group_id_editor;
SELECT @max_weight             := MAX(ROUND(weight)) from civicrm_option_value where option_group_id = @option_group_id_editor;

INSERT INTO civicrm_option_value
        ( option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, domain_id, visibility_id )
VALUES
	( @option_group_id_editor, {localize}'Joomla Default Editor'{/localize}, @max_value+1, NULL, NULL, 0, NULL, @max_weight+1, {localize}NULL{/localize}, 0, 1, 1, NULL, @domainID, NULL );

-- CRM-6846
CREATE TABLE `civicrm_price_field_value` 
  (`id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Price Field Value',
  `price_field_id` int(10) unsigned NOT NULL COMMENT 'FK to civicrm_price_field',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Price field option name',
  {localize field='label'}`label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Price field option label'{/localize},
  {localize field='description'}`description` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Price field option description.'{/localize},
  `amount` varchar(512) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Price field option amount',
  `count` int(10) unsigned DEFAULT NULL COMMENT 'Number of participants per field option',
  `max_value` int(10) unsigned DEFAULT NULL COMMENT 'Max number of participants per field options',
  `weight` int(11) DEFAULT '1' COMMENT 'Order in which the field options should appear',
  `is_default` tinyint(4) DEFAULT '0' COMMENT 'Is this default price field option',
  `is_active` tinyint(4) DEFAULT '1' COMMENT 'Is this price field option active',
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_civicrm_price_field_value_price_field_id` FOREIGN KEY (`price_field_id`) REFERENCES civicrm_price_field(id) ON DELETE CASCADE )ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--CRM-7003
 ALTER TABLE `civicrm_uf_match` ADD INDEX `I_civicrm_uf_match_uf_id`(`uf_id`);

--CRM-4572
SELECT @uf_group_id_sharedAddress   := max(id) from civicrm_uf_group where name = 'shared_address';
UPDATE civicrm_uf_field
   SET {localize field='help_post'} help_post = NULL {/localize}
WHERE civicrm_uf_field.uf_group_id = @uf_group_id_sharedAddress AND civicrm_uf_field.field_name= 'country';

--CRM-7031
ALTER TABLE `civicrm_participant` 
 CHANGE `fee_currency` `fee_currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value derived from config setting.';

ALTER TABLE `civicrm_contribution` 
  CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_grant` 
 CHANGE `currency` `currency` VARCHAR( 8 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_pcp` 
 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';

ALTER TABLE `civicrm_pledge` 
 CHANGE `currency` `currency` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.';
 
 -- insert civimail settings into nav menu
 SELECT @domainID               := MIN(id) FROM civicrm_domain;
 SELECT @nav_civimailadmin_id   := id FROM civicrm_navigation WHERE name = 'CiviMail';
 SELECT @nav_civimailadmin_wt   := MAX(ROUND(weight)) from civicrm_navigation WHERE parent_id = @nav_civimailadmin_id;

 INSERT INTO civicrm_navigation
     ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
 VALUES
     ( @domainID, 'civicrm/admin/mail&reset=1', '{ts escape="sql"}Mailer Settings{/ts}', 'Mailer Settings', 'access CiviMail,administer CiviCRM', 'AND', @nav_civimailadmin_id, '1', NULL, @nav_civimailadmin_wt + 1 );
 
 -- update petition system workflow message templates
 {include file='../CRM/Upgrade/3.3.beta1.msg_template/civicrm_msg_template.tpl'}
 
-- CRM-6231 -tweak permissions.
UPDATE  civicrm_navigation 
   SET  permission = CONCAT( permission, ',manage campaign' ),
        permission_operator = 'OR'
 WHERE  name in ( 'Dashboard', 'Survey Dashboard', 'Petition Dashboard', 'Campaign Dashboard', 'New Campaign', 'New Survey',  'New Petition' )
   AND  permission = 'administer CiviCampaign';

-- replace voter w/ respondent.
UPDATE    civicrm_navigation
   SET    label  = REPLACE(label, 'Voter', 'Respondent' ),
          name   = REPLACE(name,  'Voter', 'Respondent' )
  WHERE   name IN ( 'Reserve Voters', 'Interview Voters', 'Release Voters' );


SELECT  @campaignTypeOptGrpID := MAX(id) from civicrm_option_group where name = 'campaign_type';

UPDATE  civicrm_option_value
   SET  {localize field='label'}label = REPLACE(label, 'Voter', 'Constituent' ){/localize},
	name = REPLACE(name, 'Voter', 'Constituent' )	
 WHERE  name = 'Voter Engagement'
   AND  option_group_id = @campaignTypeOptGrpID;

UPDATE  civicrm_navigation 
   SET  permission = CONCAT( permission, ',release campaign contacts' )
 WHERE  name like 'Voter Listing'
   AND  permission = 'administer CiviCampaign,manage campaign';


{if $multilingual}
  {foreach from=$locales item=loc}
   ALTER TABLE civicrm_batch ADD label_{$loc} varchar(64);
   ALTER TABLE civicrm_batch ADD description_{$loc} text;

   UPDATE civicrm_batch SET label_{$loc} = label;
   UPDATE civicrm_batch SET description_{$loc} = description;
  {/foreach}
  ALTER TABLE civicrm_batch DROP label;
  ALTER TABLE civicrm_batch DROP description;
{/if}

-- CRM-7044 (needed for the installs that upgraded to 3.3 from pre-3.2.5)
UPDATE civicrm_state_province SET name = 'Khomas' WHERE name = 'Khomae';

