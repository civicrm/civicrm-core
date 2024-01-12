{*
Display a table of remotely-available extensions

Depends: CRM/common/enableDisableApi.tpl and CRM/common/jsortable.tpl
*}
{if $remoteExtensionRows}
  <div id="extensions-addnew">
    {strip}
    <table id="extensions-addnew-table" class="display">
      <thead>
        <tr>
          <th>{ts}Extension name (key){/ts}</th>
          <th>{ts}Version{/ts}</th>
          <th>{ts}Type{/ts}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$remoteExtensionRows key=extKey item=row}
        {if array_key_exists($extKey, $localExtensionRows)}
          {continue}
        {/if}
        <tr id="addnew-row_{$row.file}" class="crm-extensions crm-extensions_{$row.file}">
          <td class="crm-extensions-label">
            <details class="crm-accordion-light">
              <summary><strong>{$row.label|escape}</strong>
              <br/>{$row.description|escape}</summary>
                {include file="CRM/Admin/Page/ExtensionDetails.tpl" extension=$row}
            </details>
          </td>
          <td class="crm-extensions-version">{$row.version|escape}</td>
          <td class="crm-extensions-description">{$row.type|capitalize}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>
    {/strip}
  </div>
{else}
  <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
      {ts}There are no extensions to display. Please click "Refresh" to update information about available extensions.{/ts}
  </div>
{/if}
