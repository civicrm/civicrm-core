-- CRM-6228

{include file='../CRM/Upgrade/3.2.alpha3.msg_template/civicrm_msg_template.tpl'}

-- CRM-6144
   UPDATE civicrm_option_value 
LEFT JOIN civicrm_option_group ON ( civicrm_option_value.option_group_id = civicrm_option_group.id )
      SET civicrm_option_value.is_reserved = 1, civicrm_option_value.is_active = 0 
    WHERE civicrm_option_group.name = 'activity_type' 
      AND civicrm_option_value.name = 'Close Case';

-- CRM-6102
ALTER TABLE civicrm_preferences 
    ADD sort_name_format TEXT COMMENT 'Format to display contact sort name' AFTER mailing_format,
    ADD display_name_format TEXT COMMENT 'Format to display the contact display name' AFTER  mailing_format;

UPDATE civicrm_preferences 
    SET display_name_format = '{literal}{contact.individual_prefix}{ }{contact.first_name}{ }{contact.last_name}{ }{contact.individual_suffix}{/literal}', 
        sort_name_format    = '{literal}{contact.last_name}{, }{contact.first_name}{/literal}'
    WHERE is_domain = 1;

-- CRM-1496
   INSERT INTO 
   `civicrm_option_group` (`name`, {localize field='description'}`description`{/localize}, `is_reserved`, `is_active`) 
VALUES 			  
    ('currencies_enabled',{localize}'{ts escape="sql"}List of currencies enabled for this site{/ts}'{/localize}, 0, 1);
   
-- INSERT Default currency
   SELECT @option_group_id_currency       := max(id) from civicrm_option_group where name = 'currencies_enabled';
   INSERT INTO 
   `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`) 
   VALUES
   (@option_group_id_currency, {localize}'{ts escape="sql"}USD ($){/ts}'{/localize}, 'USD', 'USD',  NULL, 0, 1, 1, {localize} NULL{/localize} , 0, 0, 1, NULL, NULL);


-- CRM-1496

-- add currency field, set it to default value and modify it to not null 
-- civicrm_contribution_recur
   ALTER TABLE `civicrm_contribution_recur` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_contribution_recur` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_contribution_recur` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_grant
   ALTER TABLE `civicrm_grant` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_grant` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_grant` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_entity_financial_trxn
   ALTER TABLE `civicrm_entity_financial_trxn` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_entity_financial_trxn` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_entity_financial_trxn` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_product
   ALTER TABLE `civicrm_product` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_product` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_product` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_pcp
   ALTER TABLE `civicrm_pcp` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_pcp` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_pcp` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_pledge
   ALTER TABLE `civicrm_pledge` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_pledge` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_pledge` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_contribution_soft
   ALTER TABLE `civicrm_contribution_soft` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_contribution_soft` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_contribution_soft` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';
-- civicrm_pledge_payment
   ALTER TABLE `civicrm_pledge_payment` ADD COLUMN `currency` varchar(3) NULL COMMENT '3 character string, value from config setting or input via user.';
   UPDATE `civicrm_pledge_payment` SET `currency` = '{$config->defaultCurrency}';
   ALTER TABLE `civicrm_pledge_payment` MODIFY COLUMN `currency` varchar(3) NOT NULL COMMENT '3 character string, value from config setting or input via user.';

-- Fixing length of currency VARCHAR(64) to VARCHAR(3)
-- civicrm_financial_trxn
   ALTER TABLE `civicrm_financial_trxn` MODIFY `currency` VARCHAR(3);
-- civicrm_contribution
   ALTER TABLE `civicrm_contribution` MODIFY `currency` VARCHAR(3);
-- civicrm_participant
   UPDATE `civicrm_participant` SET `fee_currency` = '{$config->defaultCurrency}' WHERE `fee_currency` IS NULL;
   ALTER TABLE `civicrm_participant` MODIFY `fee_currency` VARCHAR(3) NOT NULL;


-- CRM-6138
{include file='../CRM/Upgrade/3.2.alpha3.languages/languages.tpl'}

ALTER TABLE `civicrm_contact` 
	ADD COLUMN `preferred_language` varchar(5) DEFAULT NULL COMMENT 'Which language is preferred for communication. FK to languages in civicrm_option_value.';

-- CRM-3854
ALTER TABLE `civicrm_country` 
	ADD COLUMN `address_format_id` int(10) unsigned DEFAULT NULL COMMENT 'Format to display the address, country specific';

CREATE TABLE `civicrm_address_format` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `format` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- CRM-6154
   ALTER TABLE civicrm_domain
   	 ADD `locale_custom_strings` text COLLATE utf8_unicode_ci COMMENT 'String Overrides';

-- CRM-6181
UPDATE `civicrm_contact` SET `is_deleted` = 0 WHERE `is_deleted` IS NULL;
ALTER TABLE `civicrm_contact` MODIFY COLUMN `is_deleted` boolean NOT NULL DEFAULT 0;

-- CRM-6198

DELETE FROM `civicrm_state_province` WHERE `name` IN ('Freeport', 'Fresh Creek', 'Governor\'s Harbour' , 'Green Turtle Cay', 'Harbour Island', 'High Rock', 'Kemps Bay', 'Marsh Harbour','Nicholls Town and Berry Islands' ,'Rock Sound','Sandy Point','San Salvador and Rum Cay','Bandundu', 'Bas-Congo' ,'Haut-Congo', 'Kasai-Occidental','Katanga', 'Orientale' );

INSERT INTO civicrm_state_province
        (`name`, `abbreviation`, `country_id` )
   VALUES
        ( 'Abaco Islands', 'AB',1212),
        ( 'Andros Island', 'AN',1212 ),
        ( 'Berry Islands', 'BR',1212 ),
        ( 'Eleuthera', 'EL', 1212 ),
        ( 'Grand Bahama', 'GB', 1212 ),
        ( 'Rum Cay','RC', 1212 ),
        ( 'San Salvador Island', 'SS', 1212 ),
        ( 'Kongo central', '01', 1050 ),
	( 'Kwango', '02', 1050 ),
	( 'Kwilu', '03', 1050 ),
	( 'Mai-Ndombe', '04', 1050 ),
	( 'Kasai', '05', 1050 ),
	( 'Lulua', '06', 1050 ),
	( 'Lomami', '07', 1050 ),
	( 'Sankuru', '08', 1050 ),
	( 'Ituri', '09', 1050 ),
	( 'Haut-Uele', '10', 1050 ),
	( 'Tshopo', '11', 1050 ),
	( 'Bas-Uele', '12', 1050 ),
	( 'Nord-Ubangi', '13', 1050 ),
	( 'Mongala', '14', 1050 ),
	( 'Sud-Ubangi', '15', 1050 ),
	( 'Tshuapa', '16', 1050 ),	
	( 'Haut-Lomami', '17', 1050 ),
	( 'Lualaba', '18', 1050 ),
	( 'Haut-Katanga', '19', 1050 ),
	( 'Tanganyika', '20', 1050 );

-- CRM-6159
UPDATE civicrm_mailing_bounce_pattern SET pattern = 'over\\s?quota' WHERE pattern = 'overs?quota';

-- CRM-6180
UPDATE civicrm_state_province SET name = 'Durrës' WHERE name = 'Durrsës';
UPDATE civicrm_state_province SET name = 'Korçë'  WHERE name = 'Korcë';
