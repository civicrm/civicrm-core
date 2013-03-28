-- CRM-12142
-- Populate default text for premiums_nothankyou_label
{if !$multilingual}
  UPDATE `civicrm_premiums` SET premiums_nothankyou_label = '{ts escape="sql"}No thank-you{/ts}';
{else}
  {foreach from=$locales item=locale}
    UPDATE `civicrm_premiums` SET premiums_nothankyou_label_{$locale} = '{ts escape="sql"}No thank-you{/ts}';	   
  {/foreach}
{/if}
