{if $recur}
  <div class="solid-border-top">
    <br /><label>{ts 1=$displayName}Recurring Contributions{/ts}</label>
  </div>
  {include file="CRM/Contribute/Page/ContributionRecurSelector.tpl" action=16 recurType='both'}
{/if}
