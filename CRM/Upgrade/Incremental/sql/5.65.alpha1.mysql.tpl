{* file to handle db changes in 5.65.alpha1 during upgrade *}

-- 5.65.alpha1 has multiple changes to civicrm_group table. Snapshot for all of them.
{crmUpgradeSnapshot name=group}
  SELECT id, name, title, frontend_title, description, frontend_description
  FROM civicrm_group
  WHERE name IS NULL OR frontend_title IS NULL OR frontend_title = "" OR frontend_description IS NULL OR frontend_description = ""
{/crmUpgradeSnapshot}

-- Ensure new name field is not null/unique. Setting to ID is a bit lazy - but it works.
UPDATE civicrm_group SET `name` = `id` WHERE name IS NULL;

-- Ensure API entity names always start with uppercase
{crmUpgradeSnapshot name=job}
  SELECT id, api_entity FROM civicrm_job
{/crmUpgradeSnapshot}
UPDATE `civicrm_job` SET `api_entity` = CONCAT(UPPER(SUBSTRING(`api_entity`, 1 ,1)), SUBSTRING(`api_entity`, 2));

-- Add name field, make frontend_title required (in conjunction with php function)
{localize field='title,frontend_title,frontend_description'}
  UPDATE `civicrm_group`
  SET `frontend_title` = `title`
  WHERE `frontend_title` IS NULL OR `frontend_title` = '';

  UPDATE `civicrm_group`
  SET `frontend_description` = `description`
  WHERE (`frontend_description` IS NULL OR `frontend_description` = '') AND description <> '';
{/localize}

{crmUpgradeSnapshot name=schedule}
  SELECT id, limit_to FROM civicrm_action_schedule
{/crmUpgradeSnapshot}
UPDATE `civicrm_action_schedule` SET `limit_to` = 2 WHERE `limit_to` = 0;

{crmUpgradeSnapshot name=mailcomp}
  SELECT id, body_html, body_text, subject FROM civicrm_mailing_component
  WHERE component_type IN ('Welcome', 'Subscribe')
{/crmUpgradeSnapshot}
{literal}
UPDATE civicrm_mailing_component
SET body_html = REPLACE(body_html, '{welcome.group}', '{group.frontend_title}'),
body_text = REPLACE(body_text, '{welcome.group}', '{group.frontend_title}'),
subject = REPLACE(subject, '{welcome.group}', '{group.frontend_title}')
WHERE component_type = 'Welcome';

UPDATE civicrm_mailing_component
SET body_html = REPLACE(body_html, '{subscribe.group}', '{group.frontend_title}'),
body_text = REPLACE(body_text, '{subscribe.group}', '{group.frontend_title}'),
subject = REPLACE(subject, '{subscribe.group}', '{group.frontend_title}')
WHERE component_type = 'Subscribe';
{/literal}

{crmUpgradeSnapshot name=loctype}
  SELECT id, name, display_name, is_reserved, is_active, is_default FROM civicrm_location_type
{/crmUpgradeSnapshot}
UPDATE `civicrm_location_type` SET `is_reserved` = 0 WHERE `is_reserved` IS NULL;
UPDATE `civicrm_location_type` SET `is_active` = 0 WHERE `is_active` IS NULL;
UPDATE `civicrm_location_type` SET `is_default` = 0 WHERE `is_default` IS NULL;
UPDATE `civicrm_location_type` SET `name` = CONCAT('location_', id) WHERE `name` IS NULL;
UPDATE `civicrm_location_type` SET {localize field=display_name}`display_name` = COALESCE(`display_name`, `name`){/localize};
