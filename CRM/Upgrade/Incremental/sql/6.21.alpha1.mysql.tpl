{* file to handle db changes in 6.21.alpha1 during upgrade *}

-- Estonia: use ISO 3166-2:EE codes as county abbreviations
SELECT @country_id := id FROM civicrm_country WHERE name = 'Estonia' AND iso_code = 'EE';
UPDATE civicrm_state_province SET abbreviation = '45' WHERE country_id = @country_id AND name = 'Ida-Virumaa';
UPDATE civicrm_state_province SET abbreviation = '50' WHERE country_id = @country_id AND name = 'Jõgevamaa';
UPDATE civicrm_state_province SET abbreviation = '52' WHERE country_id = @country_id AND name = 'Järvamaa';
UPDATE civicrm_state_province SET abbreviation = '56' WHERE country_id = @country_id AND name = 'Läänemaa';
UPDATE civicrm_state_province SET abbreviation = '60' WHERE country_id = @country_id AND name = 'Lääne-Virumaa';
UPDATE civicrm_state_province SET abbreviation = '64' WHERE country_id = @country_id AND name = 'Põlvamaa';
UPDATE civicrm_state_province SET abbreviation = '68' WHERE country_id = @country_id AND name = 'Pärnumaa';
UPDATE civicrm_state_province SET abbreviation = '71' WHERE country_id = @country_id AND name = 'Raplamaa';
UPDATE civicrm_state_province SET abbreviation = '79' WHERE country_id = @country_id AND name = 'Tartumaa';
UPDATE civicrm_state_province SET abbreviation = '81' WHERE country_id = @country_id AND name = 'Valgamaa';
UPDATE civicrm_state_province SET abbreviation = '87' WHERE country_id = @country_id AND name = 'Võrumaa';

-- StatusPreference.ignore_severity becomes required
UPDATE civicrm_status_pref SET ignore_severity = 0 WHERE ignore_severity IS NULL;

-- Update ACL menu icon
UPDATE `civicrm_navigation` SET `icon` = 'crm-i fa-users-between-lines' WHERE `name` = 'Permissions (Access Control)';

-- Update report dashlets and navigation to use synthetic permissions
UPDATE `civicrm_dashboard`
SET `permission` = CONCAT('@report:', SUBSTRING(`name`, 8)),
    `permission_operator` = 'AND'
WHERE `name` REGEXP '^report/[0-9]+$';

UPDATE `civicrm_navigation`
SET `permission` = CONCAT('@report:', SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(`url`, 'civicrm/report/instance/', -1), '?', 1), '&', 1), '#', 1)),
    `permission_operator` = 'AND'
WHERE `url` REGEXP '^/?civicrm/report/instance/[0-9]+';

-- Add the core contribution status used while an asynchronous payment is being processed.
SELECT @contribution_status_group_id := id FROM civicrm_option_group WHERE name = 'contribution_status';
SELECT @use_core_in_progress_slot := NOT EXISTS(
  SELECT 1 FROM civicrm_option_value WHERE option_group_id = @contribution_status_group_id AND value = '5'
);
SELECT @in_progress_value := IF(@use_core_in_progress_slot, 5, COALESCE(MAX(CAST(value AS UNSIGNED)), 0) + 1)
FROM civicrm_option_value WHERE option_group_id = @contribution_status_group_id;
SELECT @in_progress_weight := IF(@use_core_in_progress_slot, 5, COALESCE(MAX(weight), 0) + 1)
FROM civicrm_option_value WHERE option_group_id = @contribution_status_group_id;

INSERT INTO civicrm_option_value (
  option_group_id, {localize field='label'}label{/localize}, value, name, weight,
  {localize field='description'}description{/localize}, is_reserved, is_active, is_default
)
SELECT
  @contribution_status_group_id,
  {localize}'{ts escape="sql"}In Progress{/ts}'{/localize},
  @in_progress_value,
  'In Progress',
  @in_progress_weight,
  {localize}'{ts escape="sql"}The payer has completed their action and the payment processor is still processing the payment.{/ts}'{/localize},
  1,
  1,
  0
WHERE NOT EXISTS (
  SELECT 1 FROM civicrm_option_value
  WHERE option_group_id = @contribution_status_group_id AND name = 'In Progress'
);

UPDATE civicrm_option_value
SET is_reserved = 1, is_active = 1
WHERE option_group_id = @contribution_status_group_id AND name = 'In Progress';
