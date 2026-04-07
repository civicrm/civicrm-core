{* file to handle db changes in 6.2.alpha1 during upgrade *}

{localize field="title"}
  UPDATE civicrm_custom_group SET name = title
  WHERE name IS NULL;
{/localize}

UPDATE civicrm_custom_group SET extends = 'Contact'
WHERE extends IS NULL;

UPDATE civicrm_custom_group SET style = 'Inline'
WHERE style IS NULL OR style NOT IN ('Tab', 'Inline', 'Tab with table');

-- 1. Adds new regions if they do not exist
SELECT @country_id := id
FROM civicrm_country
WHERE name = 'Denmark' AND iso_code = 'DK'
LIMIT 1;

INSERT IGNORE INTO civicrm_state_province (id, country_id, abbreviation, name)
VALUES
(NULL, @country_id, '81', 'Nordjylland'),
(NULL, @country_id, '82', 'Midtjylland'),
(NULL, @country_id, '83', 'Syddanmark'),
(NULL, @country_id, '84', 'Hovedstaden'),
(NULL, @country_id, '85', 'Sjælland');

-- 2. Retrieves the IDs of the new regions
SET @nordjylland := (SELECT id FROM civicrm_state_province WHERE country_id = @country_id AND abbreviation = '81');
SET @midtjylland := (SELECT id FROM civicrm_state_province WHERE country_id = @country_id AND abbreviation = '82');
SET @syddanmark := (SELECT id FROM civicrm_state_province WHERE country_id = @country_id AND abbreviation = '83');
SET @hovedstaden := (SELECT id FROM civicrm_state_province WHERE country_id = @country_id AND abbreviation = '84');
SET @sjaelland := (SELECT id FROM civicrm_state_province WHERE country_id = @country_id AND abbreviation = '85');

-- 3. Updates contacts (addresses)
UPDATE civicrm_address
SET state_province_id = @nordjylland
WHERE state_province_id IN (
SELECT id FROM civicrm_state_province
WHERE name IN ('North Jutland')
AND country_id = @country_id
);

UPDATE civicrm_address
SET state_province_id = @midtjylland
WHERE state_province_id IN (
SELECT id FROM civicrm_state_province
WHERE name IN ('Århus', 'Ringkjøbing', 'Viborg')
AND country_id = @country_id
);

UPDATE civicrm_address
SET state_province_id = @syddanmark
WHERE state_province_id IN (
SELECT id FROM civicrm_state_province
WHERE name IN ('Fyn', 'Ribe', 'South Jutland', 'Vejle')
AND country_id = @country_id
);

UPDATE civicrm_address
SET state_province_id = @hovedstaden
WHERE state_province_id IN (
SELECT id FROM civicrm_state_province
WHERE name IN ('Bornholm', 'Copenhagen', 'Copenhagen City', 'Frederiksberg', 'Frederiksborg')
AND country_id = @country_id
);

UPDATE civicrm_address
SET state_province_id = @sjaelland
WHERE state_province_id IN (
SELECT id FROM civicrm_state_province
WHERE name IN ('Roskilde', 'Storstrøm', 'Vestsjælland')
AND country_id = @country_id
);

-- 4. Removes old regions
DELETE FROM civicrm_state_province
WHERE name IN (
'Århus', 'Bornholm', 'Copenhagen', 'Copenhagen City', 'Frederiksberg',
'Frederiksborg', 'Fyn', 'North Jutland', 'Ribe', 'Ringkjøbing',
'Roskilde', 'South Jutland', 'Storstrøm', 'Vejle',
'Vestsjælland', 'Viborg'
) AND country_id = @country_id;
