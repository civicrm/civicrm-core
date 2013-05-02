-- CRM-7743
SELECT @option_group_id_languages := MAX(id) FROM civicrm_option_group WHERE name = 'languages';
UPDATE civicrm_option_value SET name = 'ce_RU' WHERE value = 'ce' AND option_group_id = @option_group_id_languages;

-- CRM-7750
SELECT @option_group_id_report := MAX(id)     FROM civicrm_option_group WHERE name = 'report_template';
SELECT @weight                 := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_report;
SELECT @contributeCompId       := MAX(id)     FROM civicrm_component    WHERE name = 'CiviContribute';

INSERT INTO civicrm_option_value
  (option_group_id, {localize field='label'}label{/localize}, value, name, weight, {localize field='description'}description{/localize}, is_active, component_id) 
  VALUES
  (@option_group_id_report, {localize}'Personal Campaign Page Report'{/localize}, 'contribute/pcp', 'CRM_Report_Form_Contribute_PCP', @weight := @weight + 1, {localize}'Shows Personal Campaign Page Report.'{/localize}, 1, @contributeCompId );

-- CRM-7775
ALTER TABLE `civicrm_activity`
ADD `engagement_level` int(10) unsigned default NULL COMMENT 'Assign a specific level of engagement to this activity. Used for tracking constituents in ladder of engagement.';


{if $renameColumnVisibility}
 ALTER TABLE `civicrm_mailing` CHANGE `visibilty` `visibility` ENUM( 'User and User Admin Only', 'Public Pages' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'User and User Admin Only' COMMENT 'In what context(s) is the mailing contents visible (online viewing)';
{/if}

-- CRM-7453
 UPDATE `civicrm_navigation` 
    SET `url` = 'civicrm/activity/email/add&atype=3&action=add&reset=1&context=standalone' WHERE `name` = 'New Email';

