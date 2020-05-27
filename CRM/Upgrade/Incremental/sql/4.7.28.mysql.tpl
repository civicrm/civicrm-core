{* file to handle db changes in 4.7.28 during upgrade *}

-- CRM-21268 Missing French overseas departments.
 INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
   (NULL, 1076, "WF", "Wallis-et-Futuna"),
   (NULL, 1076, "NC", "Nouvelle-Calédonie"),

-- CRM-21283 Add in missing parishes and regions of Barbados and Antigua and Barbuda
  (NULL, 1009, "03", "Saint George"),
  (NULL, 1009, "04", "Saint John"),
  (NULL, 1009, "05", "Saint Mary"),
  (NULL, 1009, "06", "Saint Paul"),
  (NULL, 1009, "07", "Saint Peter"),
  (NULL, 1009, "08", "Saint Philip"),
  (NULL, 1009, "10", "Barbuda"),
  (NULL, 1009, "11", "Redonda"),
  (NULL, 1018, "01", "Christ Church"),
  (NULL, 1018, "02", "Saint Andrew"),
  (NULL, 1018, "03", "Saint George"),
  (NULL, 1018, "04", "Saint James"),
  (NULL, 1018, "05", "Saint John"),
  (NULL, 1018, "06", "Saint Joseph"),
  (NULL, 1018, "07", "Saint Lucy"),
  (NULL, 1018, "08", "Saint Michael"),
  (NULL, 1018, "09", "Saint Peter"),
  (NULL, 1018, "10", "Saint Philip"),
  (NULL, 1018, "11", "Saint Thomas");

-- CRM-21337 ISO compliance for Romanian and Bulgarian counties
UPDATE `civicrm_state_province` SET `name` = 'Argeș' WHERE `name` = 'Arges' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Bacău' WHERE `name` = 'Bacau' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Bistrița-Năsăud' WHERE `name` = 'Bistrita-Nasaud' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Botoșani' WHERE `name` = 'Boto\'ani' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Brașov' WHERE `name` = 'Bra\'ov' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Brăila' WHERE `name` = 'Braila' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Buzău' WHERE `name` = 'Buzau' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Caraș-Severin' WHERE `name` = 'Caras-Severin' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Călărași' WHERE `name` = 'Ca la ras\'i' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Constanța' WHERE `name` = 'Constant\'a' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Dâmbovița' WHERE `name` = 'Dambovit\'a' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Galați' WHERE `name` = 'Galat\'i' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Ialomița' WHERE `name` = 'Ialomit\'a' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Iași' WHERE `name` = 'Ias\'i' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Maramureș' WHERE `name` = 'Maramures' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Mehedinți' WHERE `name` = 'Mehedint\'i' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Mureș' WHERE `name` = 'Mures' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Neamț' WHERE `name` = 'Neamt' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Sălaj' WHERE `name` = 'Sa laj' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Timiș' WHERE `name` = 'Timis' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Vâlcea' WHERE `name` = 'Valcea' AND `country_id` = 1176;
UPDATE `civicrm_state_province` SET `name` = 'Pazardzhik' WHERE `name` = 'Pazardzik' AND `country_id` = 1033;

-- CRM-20772 Price set calculation precision when sales tax enabled
ALTER TABLE `civicrm_membership_type` CHANGE `minimum_fee` `minimum_fee` DECIMAL(18,9) NULL DEFAULT '0.00' COMMENT 'Minimum fee for this membership (0 for free/complimentary memberships).';
ALTER TABLE `civicrm_price_field_value` CHANGE `amount` `amount` DECIMAL(18,9) NOT NULL COMMENT 'Price field option amount';

