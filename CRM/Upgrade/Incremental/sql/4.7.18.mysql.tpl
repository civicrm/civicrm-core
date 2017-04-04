{* file to handle db changes in 4.7.18 during upgrade *}

-- CRM-20062 New counties of Kenya.
SELECT @country_id := max(id) from civicrm_country where iso_code = "KE";
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
(@country_id, "01", "Baringo"),
(@country_id, "02", "Bomet"),
(@country_id, "03", "Bungoma"),
(@country_id, "04", "Busia"),
(@country_id, "05", "Elgeyo/Marakwet"),
(@country_id, "06", "Embu"),
(@country_id, "07", "Garissa"),
(@country_id, "08", "Homa Bay"),
(@country_id, "09", "Isiolo"),
(@country_id, "10", "Kajiado"),
(@country_id, "11", "Kakamega"),
(@country_id, "12", "Kericho"),
(@country_id, "13", "Kiambu"),
(@country_id, "14", "Kilifi"),
(@country_id, "15", "Kirinyaga"),
(@country_id, "16", "Kisii"),
(@country_id, "17", "Kisumu"),
(@country_id, "18", "Kitui"),
(@country_id, "19", "Kwale"),
(@country_id, "20", "Laikipia"),
(@country_id, "21", "Lamu"),
(@country_id, "22", "Machakos"),
(@country_id, "23", "Makueni"),
(@country_id, "24", "Mandera"),
(@country_id, "25", "Marsabit"),
(@country_id, "26", "Meru"),
(@country_id, "27", "Migori"),
(@country_id, "28", "Mombasa"),
(@country_id, "29", "Murang'a"),
(@country_id, "30", "Nairobi City"),
(@country_id, "31", "Nakuru"),
(@country_id, "32", "Nandi"),
(@country_id, "33", "Narok"),
(@country_id, "34", "Nyamira"),
(@country_id, "35", "Nyandarua"),
(@country_id, "36", "Nyeri"),
(@country_id, "37", "Samburu"),
(@country_id, "38", "Siaya"),
(@country_id, "39", "Taita/Taveta"),
(@country_id, "40", "Tana River"),
(@country_id, "41", "Tharaka-Nithi"),
(@country_id, "42", "Trans Nzoia"),
(@country_id, "43", "Turkana"),
(@country_id, "44", "Uasin Gishu"),
(@country_id, "45", "Vihiga"),
(@country_id, "46", "Wajir"),
(@country_id, "47", "West Pokot");

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

UPDATE `civicrm_state_province` SET `name`='Uttarakhand', `abbreviation`='UT' WHERE `name` = 'Uttaranchal' AND `abbreviation`='UL';
UPDATE `civicrm_state_province` SET `name`='Yunlin County' WHERE `name` = 'Yunlin Conuty';
UPDATE `civicrm_country` SET `name`='Palestine, State of' WHERE `name` = 'Palestinian Territory';
UPDATE `civicrm_country` SET `name`='Virgin Islands, British' WHERE `name` = 'Virgin Islands,British';

-- CRM-20102 make case_type_id required
ALTER TABLE `civicrm_case` DROP FOREIGN KEY `FK_civicrm_case_case_type_id`;
ALTER TABLE `civicrm_case` MODIFY `case_type_id` int(10) unsigned NOT NULL COMMENT 'FK to civicrm_case_type.id';
ALTER TABLE `civicrm_case` ADD CONSTRAINT `FK_civicrm_case_case_type_id` FOREIGN KEY (`case_type_id`) REFERENCES `civicrm_case_type` (`id`);

--- CRM-19715 Remove Close Accounting Period code - now in an extension.
DELETE FROM civicrm_navigation
WHERE url = 'civicrm/admin/contribute/closeaccperiod?reset=1' AND name = 'Close Accounting Period';
