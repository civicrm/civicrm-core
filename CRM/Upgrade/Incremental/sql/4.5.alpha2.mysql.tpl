INSERT INTO `civicrm_dashboard`
	(`domain_id`, `name`, {localize field='label'}`label`{/localize}, `url`, `permission`, `permission_operator`, `column_no`, `is_minimized`, `fullscreen_url`, `is_fullscreen`, `is_active`, `is_reserved`, `weight`)
	VALUES
	({$domainID},'report/6', {localize}'Donor Summary'{/localize},'civicrm/report/instance/6?reset=1&section=1&snippet=5&charts=barChart','access CiviContribute','AND',0,0,'civicrm/report/instance/6?reset=1&section=1&snippet=5&charts=barChart&context=dashletFullscreen',1,1,0,4),
	({$domainID},'report/13', {localize}'Top Donors'{/localize},'civicrm/report/instance/13?reset=1&section=2&snippet=5','access CiviContribute','AND',0,0,'civicrm/report/instance/13?reset=1&section=2&snippet=5&context=dashletFullscreen',1,1,0,5),
	({$domainID},'report/25', {localize}'Event Income Summary'{/localize}, 'civicrm/report/instance/25?reset=1&section=1&snippet=5&charts=pieChart','access CiviEvent','AND',0,0,'civicrm/report/instance/25?reset=1&section=1&snippet=5&charts=pieChart&context=dashletFullscreen',1,1,0,6),
	({$domainID},'report/20', {localize}'Membership Summary'{/localize},'civicrm/report/instance/20?reset=1&section=2&snippet=5','access CiviMember','AND',0,0,'civicrm/report/instance/20?reset=1&section=2&snippet=5&context=dashletFullscreen',1,1,0,7);

UPDATE civicrm_dashboard
	SET civicrm_dashboard.url = CONCAT(SUBSTRING(url FROM 1 FOR LOCATE('&', url) - 1), '?', SUBSTRING(url FROM LOCATE('&', url) + 1))
	WHERE civicrm_dashboard.url LIKE "%&%" AND civicrm_dashboard.url NOT LIKE "%?%";

UPDATE civicrm_dashboard
	SET civicrm_dashboard.fullscreen_url = CONCAT(SUBSTRING(fullscreen_url FROM 1 FOR LOCATE('&', fullscreen_url) - 1), '?', SUBSTRING(fullscreen_url FROM LOCATE('&', fullscreen_url) + 1))
	WHERE civicrm_dashboard.fullscreen_url LIKE "%&%" AND civicrm_dashboard.fullscreen_url NOT LIKE "%?%";

-- CRM-14843 Added States for Chile and Modify Santiago Metropolitan for consistency
INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
	(NULL, 1044, "LR",  "Los Rios"),
	(NULL, 1044, "AP",  "Arica y Parinacota"),
	(NULL, 1169, "AMA", "Amazonas");

UPDATE civicrm_state_province
	SET name = "Santiago Metropolitan", abbreviation = "SM"
	WHERE name = "Region Metropolitana de Santiago" AND abbreviation = "RM";

-- CRM-14879 contact fields for scheduled reminders
INSERT INTO civicrm_action_mapping
  (entity, entity_value, entity_value_label, entity_status, entity_status_label, entity_date_start, entity_date_end, entity_recipient)
  VALUES
	( 'civicrm_contact', 'civicrm_contact', 'Date Field', 'contact_date_reminder_options', 'Annual Options', 'date_field', NULL, NULL);

INSERT INTO `civicrm_option_group` (`name`, {localize field='title'}`title`{/localize}, `is_reserved`, `is_active`, `is_locked`)
  VALUES
  ('contact_date_reminder_options', {localize}'{ts escape="sql"}Contact Date Reminder Options{/ts}'{/localize}, 1, 1, 1);

SELECT @option_group_id_contactDateMode := max(id) from civicrm_option_group where name = 'contact_date_reminder_options';

INSERT INTO `civicrm_option_value`
  (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
  VALUES
  (@option_group_id_contactDateMode, {localize}'{ts escape="sql"}Actual date only{/ts}'{/localize}, '1', 'Actual date only', NULL, NULL, 0, 1, 0, 1, 1, NULL, NULL),
  (@option_group_id_contactDateMode, {localize}'{ts escape="sql"}Each anniversary{/ts}'{/localize}, '2', 'Each anniversary', NULL, NULL, 0, 2, 0, 1, 1, NULL, NULL);
