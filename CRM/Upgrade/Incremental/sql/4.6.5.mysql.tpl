{* file to handle db changes in 4.6.5 during upgrade *}

-- CRM-16173
CREATE TABLE IF NOT EXISTS `civicrm_cxn` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Connection ID',
     `app_guid` varchar(128)    COMMENT 'Application GUID',
     `app_meta` text    COMMENT 'Application Metadata (JSON)',
     `cxn_guid` varchar(128)    COMMENT 'Connection GUID',
     `secret` text    COMMENT 'Shared secret',
     `perm` text    COMMENT 'Permissions approved for the service (JSON)',
     `options` text    COMMENT 'Options for the service (JSON)',
     `is_active` tinyint   DEFAULT 1 COMMENT 'Is connection currently enabled?',
     `created_date` timestamp NULL  DEFAULT NULL COMMENT 'When was the connection was created.',
     `modified_date` timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When the connection was created or modified.',
     `fetched_date` timestamp NULL  DEFAULT NULL COMMENT 'The last time the application metadata was fetched.' ,
    PRIMARY KEY ( `id` )
    ,     UNIQUE INDEX `UI_appid`(
        app_guid
  )
  ,     UNIQUE INDEX `UI_keypair_cxnid`(
        cxn_guid
  )
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

-- CRM-16417 add failed payment activity type
SELECT @option_group_id_act := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @option_group_id_act_wt := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @option_group_id_act_val := MAX(ROUND(value)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_act;
SELECT @contributeCompId := max(id) FROM civicrm_component where name = 'CiviContribute';

INSERT INTO
`civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
(@option_group_id_act, {localize}'{ts escape="sql"}Failed Payment{/ts}'{/localize}, @option_group_id_act_val+1,
'Failed Payment', NULL, 1, NULL, @option_group_id_act_wt+1, {localize}'{ts escape="sql"}Failed payment.{/ts}'{/localize}, 0, 1, 1, @contributeCompId, NULL);
