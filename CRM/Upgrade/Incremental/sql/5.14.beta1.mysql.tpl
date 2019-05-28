{* file to handle db changes in 5.14.beta1 during upgrade *}

SELECT @option_group_id_sfe            := max(id) from civicrm_option_group where name = 'safe_file_extension';

INSERT INTO `civicrm_option_value` (`option_group_id`,  {localize field='label'}label{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`,  {localize field='description'}description{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
VALUES  (@option_group_id_sfe, {localize}'ics'{/localize},  15, 'ics',   NULL, 0, 0, 15, {localize}NULL{/localize}, 0, 0, 1, NULL, NULL, NULL);

