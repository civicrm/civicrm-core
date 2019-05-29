{* file to handle db changes in 5.15.alpha1 during upgrade *}
--dev/core#905 Add contribution recur status option group
INSERT INTO `civicrm_option_group`  ( `name`, {localize field='title'}`title`{/localize}, `is_active`, `is_reserved`, `is_locked` ) VALUES ('contribution_recur_status', {localize}'{ts escape="sql"}Recurring Contribution Status{/ts}'{/localize}, 1, 1, 1);

SELECT @option_group_id_ps := MAX(id) FROM `civicrm_option_group` where name = 'contribution_recur_status';

INSERT INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `weight`, `is_reserved`, `is_active`, `is_default`)
SELECT @option_group_id_ps as option_group_id, {localize field='label'}`label`{/localize}, value, ov.name, weight, ov.is_reserved, ov.is_active, is_default
FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON og.id = ov.option_group_id AND og.name = 'contribution_status';
