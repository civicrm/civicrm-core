{* file to handle db changes in 6.2.alpha1 during upgrade *}

{if $multilingual}
  {foreach from=$locales item=locale}
    UPDATE civicrm_custom_group SET name = `title_{$locale}`
    WHERE name IS NULL;
  {/foreach}
{else}
  UPDATE civicrm_custom_group SET name = title
  WHERE name IS NULL;
{/if}

UPDATE civicrm_custom_group SET extends = 'Contact'
WHERE extends IS NULL;

UPDATE civicrm_custom_group SET style = 'Inline'
WHERE style IS NULL OR style NOT IN ('Tab', 'Inline', 'Tab with table');
