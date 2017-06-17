{* file to handle db changes in 4.7.20 during upgrade *}

-- CRM-20576
{if $multilingual}
  {foreach from=$locales item=locale}
    ALTER TABLE civicrm_batch CHANGE title_{$locale} title_{$locale} varchar(255);
  {/foreach}
{else}
  ALTER TABLE civicrm_batch CHANGE title title varchar(255);
{/if}
