-- CRM-7901

ALTER TABLE `civicrm_custom_group` CHANGE `extends` `extends` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'Contact' COMMENT 'Type of object this group extends (can add other options later e.g. contact_address, etc.).';

INSERT INTO civicrm_option_group
      (name, {localize field='description'}description{/localize}, is_reserved, is_active)
VALUES
      ('cg_extend_objects', {localize}'{ts escape="sql"}Objects a custom group extends to{/ts}'{/localize}, 0, 1);

SELECT @option_group_id_cgeo    := max(id) from civicrm_option_group where name = 'cg_extend_objects';

INSERT INTO civicrm_option_value
   (option_group_id, {localize field='label'}label{/localize}, value, name, grouping, filter, is_default, weight, is_optgroup, is_reserved, is_active, component_id, visibility_id)
VALUES
   (@option_group_id_cgeo,    {localize}'{ts escape="sql"}Survey{/ts}'{/localize}, 'Survey', 'civicrm_survey', NULL, 0, NULL, 1, 0, 0, 1, NULL, NULL);
