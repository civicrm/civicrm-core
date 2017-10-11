{* file to handle db changes in 4.7.24 during upgrade *}
--Add pledge status option group
INSERT INTO `civicrm_option_group`  ( `name`, {localize field='title'}`title`{/localize}, `is_active` ) VALUES ('pledge_status', {localize}'{ts escape="sql"}Pledge Status{/ts}'{/localize}, 1);

SELECT @option_group_id_ps := MAX(id) FROM `civicrm_option_group` where name = 'pledge_status';

INSERT INTO `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `is_default`, `weight`) VALUES
(@option_group_id_ps, {localize}'{ts escape="sql"}Completed{/ts}'{/localize} , 1, 'Completed', NULL, 1),
(@option_group_id_ps, {localize}'{ts escape="sql"}Pending{/ts}'{/localize}    , 2, 'Pending', NULL, 2),
(@option_group_id_ps, {localize}'{ts escape="sql"}Cancelled{/ts}'{/localize}  , 3, 'Cancelled', NULL, 3),
(@option_group_id_ps, {localize}'{ts escape="sql"}In Progress{/ts}'{/localize}, 5, 'In Progress', NULL, 4),
(@option_group_id_ps, {localize}'{ts escape="sql"}Overdue{/ts}'{/localize}    , 6, 'Overdue', NULL, 5);