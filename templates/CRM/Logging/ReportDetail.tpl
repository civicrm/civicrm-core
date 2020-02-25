{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-content-block crm-report-form-block">
  {if $rows}
    {if $raw}
      <div class="status">
        <dl>
          <dt><div class="icon inform-icon"></div></dt>
          <dd>
            {ts}WARNING: Are you sure you want to revert the below changes?{/ts}
          </dd>
        </dl>
      </div>
    {/if}
    <p>{ts 1=$whom_url 2=$whom_name|escape 3=$who_url 4=$who_name|escape 5=$log_date}Change to <a href='%1'>%2</a> made by <a href='%3'>%4</a> on %5:{/ts}</p>
    {if $layout eq 'overlay'}
      {include file="CRM/Report/Form/Layout/Overlay.tpl"}
    {else}
      {include file="CRM/Report/Form/Layout/Table.tpl"}
    {/if}
  {else}
    <div class='messages status'>
        <div class='icon inform-icon'></div>&nbsp; {ts}This report can not be displayed because there are no relevant entries in the logging tables.{/ts}
    </div>
  {/if}
  {if $layout neq 'overlay'}
  <div class="action-link">
      <a href="{$backURL}"   class="button"><span><i class="crm-i fa-chevron-left"></i> {ts}Back to Logging Summary{/ts}</span></a>
      <a href="{$revertURL}" class="button" onclick="return confirm('{$revertConfirm}');"><span><i class="crm-i fa-undo"></i> {ts}Revert These Changes{/ts}</span></a>
  </div>
  {/if}
</div>
