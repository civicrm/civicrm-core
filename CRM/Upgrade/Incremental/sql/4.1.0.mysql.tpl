
{if $multilingual}
  {foreach from=$locales item=locale}
      ALTER TABLE civicrm_pcp_block ADD link_text_{$locale} varchar(255);
      UPDATE civicrm_pcp_block SET link_text_{$locale} = link_text;
  {/foreach}
  ALTER TABLE civicrm_pcp_block DROP link_text;
{/if}