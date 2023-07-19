{* file to handle db changes in 5.65.alpha1 during upgrade *}

-- Ensure new name field is not null/unique. Setting to ID is a bit lazy - but it works.
UPDATE civicrm_group SET `name` = `id` WHERE name IS NULL;

-- Add name field, make frontend_title required (in conjunction with php function)
{if $multilingual}
    {foreach from=$locales item=locale}
      UPDATE `civicrm_group`
      SET `frontend_title_{$locale}` = `title_{$locale}`
      WHERE `frontend_title_{$locale}` IS NULL OR `frontend_title_{$locale}` = '';

      UPDATE `civicrm_group`
      SET `frontend_description_{$locale}` = `description`
      WHERE (`frontend_description_{$locale}` IS NULL OR `frontend_description_{$locale}` = '') AND `description` <> '';
    {/foreach}
{else}
  UPDATE `civicrm_group`
  SET `frontend_title` = `title`
  WHERE `frontend_title` IS NULL OR `frontend_title` = '';

  UPDATE `civicrm_group`
  SET `frontend_description` = `description`
  WHERE (`frontend_description` IS NULL OR `frontend_description` = '') AND description <> '';
{/if}

{literal}
UPDATE civicrm_mailing_component
SET body_html = REPLACE(body_html, '{welcome.group}', '{group.frontend_title}'),
body_text = REPLACE(body_text, '{welcome.group}', '{group.frontend_title}'),
subject = REPLACE(subject, '{welcome.group}', '{group.frontend_title}')
WHERE component_type = 'Welcome';
{/literal}

UPDATE `civicrm_location_type` SET `is_active` = 0 WHERE `is_active` IS NULL;
UPDATE `civicrm_location_type` SET `name` = CONCAT('location_', id) WHERE `name` IS NULL;
UPDATE `civicrm_location_type` SET `display_name` = `name` WHERE `display_name` IS NULL;
ALTER TABLE `civicrm_location_type`
  MODIFY COLUMN `name` varchar(64) NOT NULL COMMENT 'Location Type Name.',
  MODIFY COLUMN `display_name` varchar(64) NOT NULL COMMENT 'Location Type Display Name.',
  MODIFY COLUMN `is_reserved` tinyint NOT NULL DEFAULT 0 COMMENT 'Is this location type a predefined system location?',
  MODIFY COLUMN `is_active` tinyint NOT NULL DEFAULT 1 COMMENT 'Is this property active?',
  MODIFY COLUMN `is_default` tinyint NOT NULL DEFAULT 0 COMMENT 'Is this location type the default?';
