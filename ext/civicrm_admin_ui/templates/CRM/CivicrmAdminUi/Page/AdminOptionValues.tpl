<form id="bootstrap-theme">
  {if $searchName eq 'Administer_Option_Groups'}
    <div class="alert alert-info">
      {ts}CiviCRM stores configurable choices for various drop-down fields as 'option groups'. You can click <strong>Options</strong> to view the available choices.{/ts}
      <p><i class="crm-i fa-exclamation-triangle" aria-hidden="true"></i> {ts}WARNING: Many option groups are used programatically and values should be added or modified with caution.{/ts}</p>
    </div>
  {/if}
  <crm-angular-js modules="crmSearchDisplayTable">
    <crm-search-display-table
      api-entity="{$apiEntity}"
      settings="{$searchSettings|smarty:nodefaults}"
      search="'{$searchName}'"
      display="'{$displayName}'"
      filters="{$filters|smarty:nodefaults}"
    ></crm-search-display-table>
  </crm-angular-js>
</form>
