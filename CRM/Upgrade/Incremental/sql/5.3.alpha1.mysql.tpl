{* file to handle db changes in 5.3.alpha1 during upgrade *}
ALTER TABLE civicrm_custom_group ALTER column is_multiple SET DEFAULT 0;
UPDATE civicrm_custom_group SET is_multiple = 0 WHERE is_multiple IS NULL;
ALTER TABLE civicrm_custom_group ALTER column is_active SET DEFAULT 1;
ALTER TABLE civicrm_custom_field ALTER column is_view SET DEFAULT 0;
UPDATE civicrm_custom_field SET is_view = 0 WHERE is_view IS NULL;
ALTER TABLE civicrm_custom_field ALTER column is_required SET DEFAULT 0;
UPDATE civicrm_custom_field SET is_required = 0 WHERE is_required IS NULL;
ALTER TABLE civicrm_custom_field ALTER column is_searchable SET DEFAULT 0;
UPDATE civicrm_custom_field SET is_searchable = 0 WHERE is_required IS NULL;
ALTER TABLE civicrm_custom_field ALTER column is_active SET DEFAULT 1;

SET @UKCountryId = (SELECT id FROM civicrm_country cc WHERE cc.name = 'United Kingdom');
INSERT IGNORE INTO civicrm_state_province (country_id, abbreviation, name)
VALUES (@UKCountryId, 'MON', 'Monmouthshire');

{* dev/core/#152 *}
UPDATE `civicrm_custom_field` set `html_type` = "Multi-Select" WHERE `html_type` = "AdvMulti-Select";
