{* file to handle db changes in 4.7.17 during upgrade *}

-- CRM-19943
UPDATE civicrm_navigation SET url = 'civicrm/tag' WHERE url = 'civicrm/tag?reset=1';
UPDATE civicrm_navigation SET url = REPLACE(url, 'civicrm/tag', 'civicrm/tag/edit') WHERE url LIKE 'civicrm/tag?%';

-- CRM-19815, CRM-19830 update references to check_number to reflect unique name
UPDATE civicrm_uf_field SET field_name = 'contribution_check_number' WHERE field_name = 'check_number';
UPDATE civicrm_mapping_field SET name = 'contribution_check_number' WHERE name = 'check_number';

-- CRM-19993 Fixes for ISO compliance with countries and counties
INSERT INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, 1101, "CH", "Chandigarh"),
(NULL, 1083, "CP", "Central"),
(NULL, 1083, "EP", "Eastern"),
(NULL, 1083, "NP", "Northern"),
(NULL, 1083, "WP", "Western"),
(NULL, 1181, "K", "Saint Kitts"),
(NULL, 1181, "N", "Nevis"),
(NULL, 1190, "E", "Eastern"),
(NULL, 1190, "N", "Northern"),
(NULL, 1190, "S", "Southern");

UPDATE `civicrm_state_province` SET `name`='Uttarakhand', `abbreviation`='UT' WHERE `id` = 1225;
UPDATE `civicrm_state_province` SET `name`='Yunlin County' WHERE `id` = 4863;
UPDATE `civicrm_country` SET `name`='Palestine, State of' WHERE `id` = 1165;
UPDATE `civicrm_country` SET `name`='Virgin Islands, British' WHERE `id` = 1031;
