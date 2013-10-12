{* file to handle db changes in 4.5.alpha1 during upgrade *}

ALTER TABLE `civicrm_contact`
  ADD COLUMN `formal_title` varchar(64) COMMENT 'Formal (academic or similar) title in front of name. (Prof., Dr. etc.)' AFTER `suffix_id`;

ALTER TABLE `civicrm_contact`
  ADD COLUMN `communication_style_id` int(10) unsigned COMMENT 'Communication style (e.g. formal vs. familiar) to use with this contact. FK to communication styles in civicrm_option_value.' AFTER `formal_title`,
  ADD INDEX `index_communication_style_id` (`communication_style_id`);

INSERT INTO
  `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`)
VALUES
  ('communication_style', {localize}'{ts escape="sql"}Communication Style{/ts}'{/localize}, 1, 1);

SELECT @option_group_id_communication_style := max(id) from civicrm_option_group where name = 'communication_style';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Formal{/ts}'{/localize}  , 1, 'formal'  , NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_communication_style, {localize}'{ts escape="sql"}Familiar{/ts}'{/localize}, 2, 'familiar', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL);

-- Insert menu item at Administer > Communications, above the various Greeting Formats

SELECT @parent_id := `id` FROM `civicrm_navigation` WHERE `name` = 'Communications' AND `domain_id` = {$domainID};
SELECT @add_weight := MIN(`weight`) FROM `civicrm_navigation` WHERE `name` IN('Email Greeting Formats', 'Postal Greeting Formats', 'Addressee Formats') AND `parent_id` = @parent_id;

UPDATE `civicrm_navigation`
SET `weight` = `weight`+1
WHERE `parent_id` = @parent_id
AND `weight` >= @add_weight;

INSERT INTO `civicrm_navigation`
  ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
  ( {$domainID}, 'civicrm/admin/options/communication_style&group=communication_style&reset=1', '{ts escape="sql" skip="true"}Communication Style Options{/ts}', 'Communication Style Options', 'administer CiviCRM', '', @parent_id, '1', NULL, @add_weight );

-- CRM-9988 Change world region of Panama country to America South, Central, North and Caribbean
UPDATE `civicrm_country` SET `region_id` = 2 WHERE `id` = 1166;

SELECT @option_group_id_contact_edit_options := max(id) from civicrm_option_group where name = 'contact_edit_options';

INSERT INTO
  `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Prefix{/ts}'{/localize}      , 12, 'Prefix'      , NULL, 2, NULL, 12, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Formal Title{/ts}'{/localize}, 13, 'Formal Title', NULL, 2, NULL, 13, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}First Name{/ts}'{/localize}  , 14, 'First Name'  , NULL, 2, NULL, 14, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Middle Name{/ts}'{/localize} , 15, 'Middle Name' , NULL, 2, NULL, 15, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Last Name{/ts}'{/localize}   , 16, 'Last Name'   , NULL, 2, NULL, 16, NULL, 0, 0, 1, NULL, NULL),
  (@option_group_id_contact_edit_options, {localize}'{ts escape="sql"}Suffix{/ts}'{/localize}      , 17, 'Suffix'      , NULL, 2, NULL, 17, NULL, 0, 0, 1, NULL, NULL);
