{* file to handle db changes in 6.19.alpha1 during upgrade *}

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
