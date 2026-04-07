<div id="report-tab-set-developer" role="tabpanel" class="civireport-criteria">
  <p><b>{ts}Class used{/ts}: {$report_class|escape}</b></p>
  <p>{ts}SQL Modes{/ts}:
  {foreach from=$sqlModes item=sqlMode}
    {$sqlMode|escape}
  {/foreach}
  </p>
  <pre>{$sql|purify}</pre>
</div>
