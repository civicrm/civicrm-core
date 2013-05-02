{if $addWightForActivity}
  ALTER TABLE `civicrm_activity` ADD weight INT( 11 ) NULL DEFAULT NULL;
{/if}

-- CRM-8508
SELECT @option_group_id_cgeo := max(id) from civicrm_option_group where name = 'cg_extend_objects';

INSERT INTO civicrm_option_value 
   (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@option_group_id_cgeo, {localize}'{ts escape="sql"}Cases{/ts}'{/localize},  'Case',   'civicrm_case',   NULL, 0, NULL, 2, {localize}'CRM_Case_PseudoConstant::caseType;'{/localize}, 0, 0, 1, NULL, NULL);
