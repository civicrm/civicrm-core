{* file to handle db changes in 5.77.alpha1 during upgrade *}

-- Add missing state for Moldova
SELECT @country_id := id FROM civicrm_country WHERE name = 'Moldova' AND iso_code = 'MD';
INSERT IGNORE INTO `civicrm_state_province` (`id`, `country_id`, `abbreviation`, `name`) VALUES
(NULL, @country_id, 'AN', 'Anenii Noi'),
(NULL, @country_id, 'BS', 'Basarabeasca'),
(NULL, @country_id, 'BR', 'Briceni'),
(NULL, @country_id, 'CT', 'Cantemir'),
(NULL, @country_id, 'CL', 'Călărași'),
(NULL, @country_id, 'CS', 'Căușeni'),
(NULL, @country_id, 'CM', 'Cimislia'),
(NULL, @country_id, 'CR', 'Criuleni'),
(NULL, @country_id, 'DO', 'Dondușeni'),
(NULL, @country_id, 'DR', 'Drochia'),
(NULL, @country_id, 'DU', 'Dubăsari'),
(NULL, @country_id, 'FA', 'Fălești'),
(NULL, @country_id, 'FL', 'Florești'),
(NULL, @country_id, 'GL', 'Glodeni'),
(NULL, @country_id, 'HI', 'Hîncești'),
(NULL, @country_id, 'IA', 'Ialoveni'),
(NULL, @country_id, 'LE', 'Leova'),
(NULL, @country_id, 'NI', 'Nisporeni'),
(NULL, @country_id, 'OC', 'Ocnița'),
(NULL, @country_id, 'RE', 'Rezina'),
(NULL, @country_id, 'RI', 'Rîșcani'),
(NULL, @country_id, 'SI', 'Sîngerei'),
(NULL, @country_id, 'ST', 'Strășeni'),
(NULL, @country_id, 'SD', 'Șoldănești'),
(NULL, @country_id, 'SV', 'Ștefan Vodă'),
(NULL, @country_id, 'TE', 'Telenești');
