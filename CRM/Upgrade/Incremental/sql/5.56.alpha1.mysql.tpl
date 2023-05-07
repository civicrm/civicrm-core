{* file to handle db changes in 5.56.alpha1 during upgrade *}

-- Add in Year prior to previous fiscal year and Previous 2 fiscal years
SELECT @option_group_id_date_filter := max(id) from civicrm_option_group where name = 'relative_date_filters';

SELECT @max_wt := max(weight) from civicrm_option_value where option_group_id = @option_group_id_date_filter;
INSERT INTO
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
SELECT
  @option_group_id_date_filter, {localize}'{ts escape="sql"}Previous 2 fiscal years{/ts}'{/localize}, 'previous_2.fiscal_year', 'previous_2.fiscal_year', NULL, NULL, 0, (SELECT @max_wt := @max_wt+1), {localize}NULL{/localize}, 0, 0, 1, NULL, NULL, NULL
-- needed for mariadb
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM civicrm_option_value WHERE `value`='previous_2.fiscal_year' AND `option_group_id` = @option_group_id_date_filter);

SELECT @max_wt := max(weight) from civicrm_option_value where option_group_id = @option_group_id_date_filter;
INSERT INTO
  `civicrm_option_value` (`option_group_id`, {localize field='label'}`label`{/localize}, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, {localize field='description'}`description`{/localize}, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`, `icon`)
SELECT
  @option_group_id_date_filter, {localize}'{ts escape="sql"}Fiscal year prior to previous fiscal year{/ts}'{/localize}, 'previous_before.fiscal_year', 'previous_before.fiscal_year', NULL, NULL, 0, (SELECT @max_wt := @max_wt+1), {localize}NULL{/localize}, 0, 0, 1, NULL, NULL, NULL
-- needed for mariadb
FROM DUAL
WHERE NOT EXISTS (SELECT * FROM civicrm_option_value WHERE `value`='previous_before.fiscal_year' AND `option_group_id` = @option_group_id_date_filter);

-- dev/core#3905 Update data type for data to LONGTEXT
ALTER TABLE civicrm_job_log MODIFY COLUMN data LONGTEXT COMMENT 'Potential extended data for specific job run (e.g. tracebacks).';

-- Add in missing indian states as per iso-3166-2
SELECT @indianCountryID := id FROM civicrm_country WHERE name = 'India' AND iso_code = 'IN';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
 (@indianCountryID, "LA", "Ladākh"),
 (@indianCountryID, "DH", "Dādra and Nagar Haveli and Damān and Diu");

SELECT @DHStateID := id from civicrm_state_province WHERE country_id = @indianCountryID AND abbreviation = 'DH';

UPDATE civicrm_address ca
INNER JOIN civicrm_state_province csp ON csp.id = ca.state_province_id
SET ca.state_province_id = @DHStateID
WHERE csp.country_id = @indianCountryID AND csp.abbreviation IN ("DN", "DD");

UPDATE civicrm_state_province SET is_active = 0 WHERE country_id = @indianCountryID AND abbreviation IN ("DN", "DD");

-- Fix incorrect civicrm_preferences_date description for activityDate and searchDate

UPDATE civicrm_preferences_date SET description = '{ts escape="sql"}Date for relationships. activities. contributions: receive, receipt, cancel. membership: join, start, renew. case: start, end.{/ts}' WHERE civicrm_preferences_date.name = 'activityDate';

UPDATE civicrm_preferences_date SET description = '{ts escape="sql"}Used in search forms.{/ts}' WHERE civicrm_preferences_date.name = 'searchDate';

-- dev/core#3926 Need to increase data size for 'url' column on 'civicrm_website' table
ALTER TABLE civicrm_website CHANGE url url VARCHAR( 255 );
