{* file to handle db changes in 5.56.alpha1 during upgrade *}

-- dev/core#3905 Update data type for data to LONGTEXT
ALTER TABLE civicrm_job_log MODIFY COLUMN data LONGTEXT COMMENT 'Potential extended data for specific job run (e.g. tracebacks).';

-- Add in missing indian states as per iso-3166-2
SELECT @indianCountryID := id FROM civicrm_country WHERE name = 'India' AND iso_code = 'IN';
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name) VALUES
 (@indianCountryID, "LA", "Ladākh"),
 (@indianCountryID, "DH", "Dādra and Nagar Haveli and Damān and Diu");

SELECT @DHStateID := id from civicrm_state_province WHERE country_id = @indianCountryID AND abbreviation = 'DH';

UPDATE civicrm_address ca
INNER JOIN civicrm_state_province csp ON csp.id = ca.state_province_id
SET ca.state_province_id = @DHStateID
WHERE csp.country_id = @indianCountryID AND csp.abbreviation IN ("DN", "DD");

UPDATE civicrm_state_province SET is_active = 0 WHERE country_id = @indianCountryID AND abbreviation IN ("DN", "DD");
