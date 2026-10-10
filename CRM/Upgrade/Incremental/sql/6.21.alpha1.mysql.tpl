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

-- Update ACL menu icon
UPDATE `civicrm_navigation` SET `icon` = 'crm-i fa-users-between-lines' WHERE `name` = 'Permissions (Access Control)';

-- Add a contribution status for asynchronous payments awaiting a processor result.
SELECT @option_group_id_contribution_status := MAX(id) FROM civicrm_option_group WHERE name = 'contribution_status';
SELECT @max_contribution_status_value := MAX(CAST(value AS UNSIGNED)) FROM civicrm_option_value WHERE option_group_id = @option_group_id_contribution_status;
SELECT @max_contribution_status_weight := MAX(weight) FROM civicrm_option_value WHERE option_group_id = @option_group_id_contribution_status;

INSERT INTO civicrm_option_value (
  option_group_id, {localize field='label'}label{/localize}, value, name, weight,
  {localize field='description'}description{/localize}, is_reserved, is_active, is_default
)
SELECT
  @option_group_id_contribution_status,
  {localize}'{ts escape="sql"}Pending (Processing){/ts}'{/localize},
  @max_contribution_status_value + 1,
  'Pending (Processing)',
  @max_contribution_status_weight + 1,
  {localize}'{ts escape="sql"}The payer has completed their action and the payment processor is still processing the payment.{/ts}'{/localize},
  1,
  1,
  0
WHERE NOT EXISTS (
  SELECT id
  FROM civicrm_option_value
  WHERE option_group_id = @option_group_id_contribution_status
    AND name = 'Pending (Processing)'
);

UPDATE civicrm_option_value
SET is_reserved = 1, is_active = 1
WHERE option_group_id = @option_group_id_contribution_status
  AND name = 'Pending (Processing)';
