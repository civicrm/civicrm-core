{* file to handle db changes in 5.33.alpha1 during upgrade *}
{* Core#2189 Activity Type cleanup *}
UPDATE civicrm_option_value cov JOIN civicrm_option_group cog ON cov.option_group_id = cog.id AND cog.name = 'activity_type' SET cov.component_id = 2 WHERE cov.name IN ('Downloaded Invoice', 'Emailed Invoice');
UPDATE civicrm_option_value cov JOIN civicrm_option_group cog ON cov.option_group_id = cog.id AND cog.name = 'activity_type' SET cov.component_id = 4 WHERE cov.name IN ('Bulk Email', 'Mass SMS');
UPDATE civicrm_option_value cov JOIN civicrm_option_group cog ON cov.option_group_id = cog.id AND cog.name = 'activity_type' SET cov.is_reserved = 0, cov.is_active = 0 WHERE cov.name IN ('Meeting', 'Phone Call');