-- remove activity_medium option group and values

DELETE ov.*
FROM  civicrm_option_value ov
INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id
WHERE og.name like 'activity_medium';

DELETE FROM civicrm_option_group WHERE name like 'activity_medium';

ALTER TABLE `civicrm_activity` 
  MODIFY `medium_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'Activity Medium, Implicit FK to civicrm_option_value where option_group = encounter_medium.';
