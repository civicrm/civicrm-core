{* file to handle db changes in 4.7.25 during upgrade *}

--CRM-21061 Increase report_id size from 64 to 512 to match civicrm_option_value.value column
ALTER TABLE civicrm_report_instance CHANGE COLUMN report_id report_id varchar(512) COMMENT 'FK to civicrm_option_value for the report template';

-- CRM-18231 Environment variables support
INSERT INTO civicrm_option_group
  (name, {localize field='title'}title{/localize}, is_reserved, is_active) VALUES ('environment', {localize}'{ts escape="sql"}Environment{/ts}'{/localize}, 0, 1);

SELECT @option_group_id_env := max(id) from civicrm_option_group where name = 'environment';
INSERT INTO civicrm_option_value (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, {localize field='description'}description{/localize}, is_optgroup, is_reserved, is_active, component_id, visibility_id)
 VALUES
    (@option_group_id_env, {localize}'{ts escape="sql"}Production{/ts}'{/localize}, 'Production', 'Production', NULL, 0, 1, 1, {localize}'{ts escape="sql"}Production Environment{/ts}'{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_env, {localize}'{ts escape="sql"}Staging{/ts}'{/localize}, 'Staging', 'Staging', NULL, 0, NULL, 2, {localize}'{ts escape="sql"}Staging Environment{/ts}'{/localize}, 0, 0, 1, NULL, NULL),
    (@option_group_id_env, {localize}'{ts escape="sql"}Development{/ts}'{/localize}, 'Development', 'Development', NULL, 0, NULL, 3, {localize}'{ts escape="sql"}Development Environment{/ts}'{/localize}, 0, 0, 1, NULL, NULL);

-- CRM-20935 Clean up orphaned profile links for deleted events
DELETE civicrm_uf_join
FROM civicrm_uf_join
LEFT JOIN civicrm_event e on civicrm_uf_join.entity_id = e.id
WHERE (civicrm_uf_join.module = 'CiviEvent' OR civicrm_uf_join.module = 'CiviEvent_Additional')
AND e.id IS NULL;
