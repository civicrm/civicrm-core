{* file to handle db changes in 4.6.9 during upgrade *}

-- CRM-17112 - Add Missing countries Saint Barthélemy and Saint Martin
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Barthélemy", "BL", "2", "0");
INSERT INTO civicrm_country (name,iso_code,region_id,is_province_abbreviated) VALUES("Saint Martin (French part)", "MF", "2", "0");
