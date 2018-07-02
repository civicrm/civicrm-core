{* file to handle db changes in 4.7.30 during upgrade *}

-- CRM-21407 ISO compliance for German counties
UPDATE `civicrm_state_province` SET `name` = 'Baden-Württemberg' WHERE `name` = 'Baden-Wuerttemberg' AND `country_id` = 1082;
UPDATE `civicrm_state_province` SET `abbreviation` = 'BE' WHERE `name` = 'Berlin' AND `abbreviation` = 'BR' AND `country_id` = 1082;
UPDATE `civicrm_state_province` SET `name` = 'Thüringen' WHERE `name` = 'Thueringen' AND `country_id` = 1082;

-- CRM-21378 Ensure that email abuse reports are treated as spam
SELECT @bounceTypeID := max(id) FROM civicrm_mailing_bounce_type WHERE name = 'Spam';
INSERT INTO civicrm_mailing_bounce_pattern (bounce_type_id, pattern) VALUES (@bounceTypeID, 'abuse report');


-- CRM-21532 Add French state/departments
SELECT @country_id := id from civicrm_country where name = 'France' AND iso_code = 'FR';
INSERT INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, "52", "Haute-Marne");