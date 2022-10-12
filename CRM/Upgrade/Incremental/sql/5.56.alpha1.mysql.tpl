{* file to handle db changes in 5.56.alpha1 during upgrade *}

-- Add in missing indian states as per iso-3166-2
SELECT @indianCountryID := id FROM civicrm_country WHERE name = 'India' AND iso_code = 'IN';
INSERT INTO civicrm_state_province (country_id, abbreviation, name) VALUES
 (@indianCountryID, "LA", "Ladākh"),
 (@indianCountryID, "DH", "Dādra and Nagar Haveli and Damān and Diu");

SELECT @DHStateID := id from civicrm_state_province WHERE country_id = @indianCountryID AND abbreviation = 'DH';

UPDATE civicrm_address ca
INNER JOIN civicrm_state_province csp ON csp.id = ca.state_province_id
SET ca.state_province_id = @DHStateID
WHERE csp.country_id = @indianCountryID AND csp.abbreviation IN ("DN", "DD");

UPDATE civicrm_state_province SET is_active = 0 WHERE country_id = @indianCountryID AND abbreviation IN ("DN", "DD");

-- Fix incorrect civicrm_preferences_date description for activityDate and searchDate

UPDATE civicrm_preferences_date SET description = '{ts escape="sql"}Date for relationships. activities. contributions: receive, receipt, cancel. membership: join, start, renew. case: start, end.{/ts}' WHERE civicrm_preferences_date.name = 'activityDate';

UPDATE civicrm_preferences_date SET description = '{ts escape="sql"}Used in search forms.{/ts}' WHERE civicrm_preferences_date.name = 'searchDate';
