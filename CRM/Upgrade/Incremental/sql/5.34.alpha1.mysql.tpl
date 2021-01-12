{* file to handle db changes in 5.34.alpha1 during upgrade *}

-- Add missing state for South Korea
SELECT @country_id := id from civicrm_country where name = 'Korea, Republic of' AND iso_code = 'KR';
INSERT IGNORE INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, '50', 'Sejong');

-- Remove any references to custom fields from mapping field table that no longer exist
DELETE FROM civicrm_mapping_field
WHERE name NOT IN ( SELECT concat('custom_', id) FROM civicrm_custom_field)
AND name LIKE 'custom_%';
