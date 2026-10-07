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
