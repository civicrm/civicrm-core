{* file to handle db changes in 6.20.alpha1 during upgrade *}

-- dev/core#3871 : add activity for 'Write-off' feature for pledges
SELECT @option_group_id_activity_type := max(id) from civicrm_option_group where name = 'activity_type';
SELECT @max_val    := MAX(ROUND(op.value)) FROM civicrm_option_value op WHERE op.option_group_id  = @option_group_id_activity_type;
SELECT @max_wt     := max(weight) from civicrm_option_value where option_group_id=@option_group_id_activity_type;
SELECT @pledgeCompId := id FROM `civicrm_component` where `name` like 'CiviPledge';

INSERT INTO civicrm_option_value
(option_group_id, {localize field='label'}label{/localize},
 value, name, weight, filter, component_id)
SELECT
  @option_group_id_activity_type,
  {localize}'{ts escape="sql"}Pledge write-off{/ts}'{/localize},
  (SELECT @max_val := @max_val + 1),
  'Pledge write-off',
  @max_wt + 1,
  0,
  @pledgeCompId
WHERE NOT EXISTS (
  SELECT id
  FROM civicrm_option_value
  WHERE option_group_id = @option_group_id_activity_type
    AND name = 'Pledge write-off'
);

-- Add target _blank to offsite links
UPDATE `civicrm_navigation` SET `target` = '_blank' WHERE `url` LIKE '%civicrm.org/%';

-- Jamaica: use ISO 3166-2:JM codes as province abbreviations
SELECT @country_id := id FROM civicrm_country WHERE name = 'Jamaica' AND iso_code = 'JM';
UPDATE civicrm_state_province SET abbreviation = '01' WHERE country_id = @country_id AND name = 'Kingston';
UPDATE civicrm_state_province SET abbreviation = '02' WHERE country_id = @country_id AND name = 'Saint Andrew';
UPDATE civicrm_state_province SET abbreviation = '03' WHERE country_id = @country_id AND name = 'Saint Thomas';
UPDATE civicrm_state_province SET abbreviation = '04' WHERE country_id = @country_id AND name = 'Portland';
UPDATE civicrm_state_province SET abbreviation = '05' WHERE country_id = @country_id AND name = 'Saint Mary';
UPDATE civicrm_state_province SET abbreviation = '06' WHERE country_id = @country_id AND name = 'Saint Ann';
UPDATE civicrm_state_province SET abbreviation = '07' WHERE country_id = @country_id AND name = 'Trelawny';
UPDATE civicrm_state_province SET abbreviation = '08' WHERE country_id = @country_id AND name = 'Saint James';
UPDATE civicrm_state_province SET abbreviation = '09' WHERE country_id = @country_id AND name = 'Hanover';
UPDATE civicrm_state_province SET abbreviation = '10' WHERE country_id = @country_id AND name = 'Westmoreland';
UPDATE civicrm_state_province SET abbreviation = '11' WHERE country_id = @country_id AND name = 'Saint Elizabeth';
UPDATE civicrm_state_province SET abbreviation = '12' WHERE country_id = @country_id AND name = 'Manchester';
UPDATE civicrm_state_province SET abbreviation = '13' WHERE country_id = @country_id AND name = 'Clarendon';
UPDATE civicrm_state_province SET abbreviation = '14' WHERE country_id = @country_id AND name = 'Saint Catherine';
