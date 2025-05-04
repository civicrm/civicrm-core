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
WHERE name IN ('North Jutland', 'Ribe')
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
WHERE name IN ('Fyn', 'South Jutland', 'Vejle')
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

-- Updates breadcrumbs for CiviImport;
DELETE FROM civicrm_menu WHERE path = 'civicrm/import';
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:15:"Import Contacts";s:3:"url";s:31:"/civicrm/import/contact?reset=1";}}' WHERE path IN ('civicrm/import/contact', 'civicrm/import/contact/summary');
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:17:"Import Activities";s:3:"url";s:32:"/civicrm/import/activity?reset=1";}}' WHERE path = 'civicrm/import/activity';
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:20:"Import Contributions";s:3:"url";s:36:"/civicrm/import/contribution?reset=1";}}' WHERE path = 'civicrm/import/contribution';
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:30:"Import Multi-value Custom Data";s:3:"url";s:30:"/civicrm/import/custom?reset=1";}}' WHERE path = 'civicrm/import/custom';
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:19:"Import Participants";s:3:"url";s:35:"/civicrm/import/participant?reset=1";}}' WHERE path = 'civicrm/import/participant';
UPDATE civicrm_menu SET breadcrumb = 'a:2:{i:0;a:2:{s:5:"title";s:7:"CiviCRM";s:3:"url";s:16:"/civicrm?reset=1";}i:1;a:2:{s:5:"title";s:18:"Import Memberships";s:3:"url";s:34:"/civicrm/import/membership?reset=1";}}' WHERE path = 'civicrm/import/membership';

