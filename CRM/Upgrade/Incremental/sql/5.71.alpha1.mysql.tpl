{* file to handle db changes in 5.71.alpha1 during upgrade *}


-- Add fields and indexes to civicrm_custom_field
ALTER TABLE `civicrm_custom_field`
ADD `fk_entity_on_delete` VARCHAR(255) NOT NULL DEFAULT 'set_null' COMMENT 'Behavior if referenced entity is deleted.' AFTER `fk_entity`;

-- Add name field, make frontend_title required (in conjunction with php function)
{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE `civicrm_uf_group`
    SET `frontend_title_{$locale}` = `title_{$locale}`, `name` = CONCAT(`title_{$locale}`, `id`)
    WHERE `frontend_title_{$locale}` IS NULL OR `frontend_title_{$locale}` = '';

    UPDATE `civicrm_uf_group`
    SET `name` = CONCAT(`title_{$locale}`, `id`)
    WHERE `name` IS NULL OR `name` = '';
  {/foreach}
{else}
  UPDATE `civicrm_uf_group`
  SET `frontend_title` = `title`, `name` = CONCAT(`title`, `id`)
  WHERE `frontend_title` IS NULL OR `frontend_title` = '';

  UPDATE `civicrm_uf_group`
  SET `name` = CONCAT(`title`, `id`)
  WHERE `name` IS NULL OR `name` = '';
{/if}
