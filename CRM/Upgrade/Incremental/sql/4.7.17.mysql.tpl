{* file to handle db changes in 4.7.17 during upgrade *}

-- CRM-19943
UPDATE civicrm_navigation SET url = 'civicrm/tag' WHERE url = 'civicrm/tag?reset=1';
UPDATE civicrm_navigation SET url = REPLACE(url, 'civicrm/tag', 'civicrm/tag/edit') WHERE url LIKE 'civicrm/tag?%';

-- CRM-19815, CRM-19830 update references to check_number to reflect unique name
UPDATE civicrm_uf_field SET field_name = 'contribution_check_number' WHERE field_name = 'check_number';
UPDATE civicrm_mapping_field SET name = 'contribution_check_number' WHERE name = 'check_number';

-- CRM-20062 New counties of Kenya.
SELECT @country_id := max(id) from civicrm_country where iso_code = "KE";
UPDATE civicrm_state_province set name = CONCAT("Former province - ", name) WHERE country_id = @country_id;
INSERT INTO civicrm_state_province (id, country_id, abbreviation, name) VALUES
  (NULL, @country_id, "01", "Baringo"),
  (NULL, @country_id, "02", "Bomet"),
  (NULL, @country_id, "03", "Bungoma"),
  (NULL, @country_id, "04", "Busia"),
  (NULL, @country_id, "05", "Elgeyo/Marakwet"),
  (NULL, @country_id, "06", "Embu"),
  (NULL, @country_id, "07", "Garissa"),
  (NULL, @country_id, "08", "Homa Bay"),
  (NULL, @country_id, "09", "Isiolo"),
  (NULL, @country_id, "10", "Kajiado"),
  (NULL, @country_id, "11", "Kakamega"),
  (NULL, @country_id, "12", "Kericho"),
  (NULL, @country_id, "13", "Kiambu"),
  (NULL, @country_id, "14", "Kilifi"),
  (NULL, @country_id, "15", "Kirinyaga"),
  (NULL, @country_id, "16", "Kisii"),
  (NULL, @country_id, "17", "Kisumu"),
  (NULL, @country_id, "18", "Kitui"),
  (NULL, @country_id, "19", "Kwale"),
  (NULL, @country_id, "20", "Laikipia"),
  (NULL, @country_id, "21", "Lamu"),
  (NULL, @country_id, "22", "Machakos"),
  (NULL, @country_id, "23", "Makueni"),
  (NULL, @country_id, "24", "Mandera"),
  (NULL, @country_id, "25", "Marsabit"),
  (NULL, @country_id, "26", "Meru"),
  (NULL, @country_id, "27", "Migori"),
  (NULL, @country_id, "28", "Mombasa"),
  (NULL, @country_id, "29", "Murang'a"),
  (NULL, @country_id, "30", "Nairobi City"),
  (NULL, @country_id, "31", "Nakuru"),
  (NULL, @country_id, "32", "Nandi"),
  (NULL, @country_id, "33", "Narok"),
  (NULL, @country_id, "34", "Nyamira"),
  (NULL, @country_id, "35", "Nyandarua"),
  (NULL, @country_id, "36", "Nyeri"),
  (NULL, @country_id, "37", "Samburu"),
  (NULL, @country_id, "38", "Siaya"),
  (NULL, @country_id, "39", "Taita/Taveta"),
  (NULL, @country_id, "40", "Tana River"),
  (NULL, @country_id, "41", "Tharaka-Nithi"),
  (NULL, @country_id, "42", "Trans Nzoia"),
  (NULL, @country_id, "43", "Turkana"),
  (NULL, @country_id, "44", "Uasin Gishu"),
  (NULL, @country_id, "45", "Vihiga"),
  (NULL, @country_id, "46", "Wajir"),
  (NULL, @country_id, "47", "West Pokot");
