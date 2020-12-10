{* file to handle db changes in 5.31.alpha1 during upgrade *}

{* Remove Country & State special select fields *}
UPDATE civicrm_custom_field SET html_type = 'Select'
WHERE html_type IN ('Select Country', 'Select State/Province');

{* make period_type required - it already is so the update is precautionary *}
UPDATE civicrm_membership_type SET period_type = 'rolling' WHERE period_type IS NULL;
ALTER TABLE civicrm_membership_type MODIFY `period_type` varchar(8) NOT NULL COMMENT 'Rolling membership period starts on signup date. Fixed membership periods start on fixed_period_start_day.';

{* dev/core#2027 Add missing sub-divisions for Northern Ireland and Wales *}
SET @UKCountryId = (SELECT id FROM civicrm_country cc WHERE cc.name = 'United Kingdom');
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
(@UKCountryId, "ANN", "Antrim and Newtownabbey"),
(@UKCountryId, "AND", "Ards and North Down"),
(@UKCountryId, "ABC", "Armagh City, Banbridge and Craigavon"),
(@UKCountryId, "BFS", "Belfast"),
(@UKCountryId, "CCG", "Causeway Coast and Glens"),
(@UKCountryId, "DRS", "Derry City and Strabane"),
(@UKCountryId, "FMO", "Fermanagh and Omagh"),
(@UKCountryId, "LBC", "Lisburn and Castlereagh"),
(@UKCountryId, "MEA", "Mid and East Antrim"),
(@UKCountryId, "MUL", "Mid Ulster"),
(@UKCountryId, "NMD", "Newry, Mourne and Down"),
(@UKCountryId, "BGW", "Blaenau Gwent"),
(@UKCountryId, "BGE", "Bridgend"),
(@UKCountryId, "CAY", "Caerphilly"),
(@UKCountryId, "CRF", "Cardiff"),
(@UKCountryId, "CRF", "Carmarthenshire"),
(@UKCountryId, "CGN", "Ceredigion"),
(@UKCountryId, "CWY", "Conwy"),
(@UKCountryId, "DEN", "Denbighshire"),
(@UKCountryId, "FLN", "Flintshire"),
(@UKCountryId, "AGY", "Isle of Anglesey"),
(@UKCountryId, "MTY", "Merthyr Tydfil"),
(@UKCountryId, "NTL", "Neath Port Talbot"),
(@UKCountryId, "NWP", "Newport"),
(@UKCountryId, "PEM", "Pembrokeshire"),
(@UKCountryId, "RCT", "Rhondda, Cynon, Taff"),
(@UKCountryId, "SWA", "Swansea"),
(@UKCountryId, "TOF", "Torfaen"),
(@UKCountryId, "VGL", "Vale of Glamorgan, The"),
(@UKCountryId, "WRX", "Wrexham");

ALTER TABLE civicrm_price_set MODIFY COLUMN `min_amount` decimal(20,2) DEFAULT '0.00' COMMENT 'Minimum Amount required for this set.';
