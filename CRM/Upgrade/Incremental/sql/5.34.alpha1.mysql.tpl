{* file to handle db changes in 5.34.alpha1 during upgrade *}

-- Add missing state for South Korea
SELECT @country_id := id from civicrm_country where name = 'Korea, Republic of' AND iso_code = 'KR';
INSERT IGNORE INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, '50', 'Sejong');
