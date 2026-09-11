{*
Display a table of remotely-available unreviewed extensions

Depends: CRM/common/enableDisableApi.tpl and CRM/common/jsortable.tpl
*}
{if $remoteExtensionRows}
  <div id="extensions-addother-inner">
    <div class="crm-error alert alert-warning">
      <p>{ts}These extensions have not been reviewed by the community and therefore may conflict with your configuration.{/ts} {ts}Please proceed with caution.{/ts}</p>
    </div>
    {strip}
    <table id="extensions-addnew-table" class="display">
      <thead>
        <tr>
          <th>{ts}Extension{/ts}</th>
          <th id="nosort">{ts}Version{/ts}</th>
          <th>{ts}Stability{/ts}</th>
          <th>{ts}Category{/ts}</th>
          <th>{ts}Usage{/ts}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$remoteExtensionRows key=extKey item=row}
          {if array_key_exists($extKey, $localExtensionRows)}
            {continue}
          {/if}
          {if $row.ready == 'ready'}
            {continue}
          {/if}
          <tr id="addnew-row_{$row.file}" class="crm-extension-row crm-extensions crm-extensions_{$row.file} {cycle values="odd-row,even-row"}">
            <td class="crm-extensions-label">
              <details class="crm-accordion-light">
                <summary>
                  <strong>{$row.label|escape}</strong>
                  <br/>{$row.description|escape}
                </summary>
                {include file="CRM/Admin/Page/ExtensionDetails.tpl" extension=$row}
              </details>
            </td>
            <td class="crm-extensions-version right">{$row.version|escape}</td>
            <td class="crm-extensions-stability right">
              {if $row.develStage_formatted != ''}
                {$row.develStage_formatted|escape}
              {else}{ts}Unknown{/ts}
              {/if}
            </td>
            <td class="crm-extensions-category right">
              {if $row.category != ''}
                {$row.category|escape}
              {else}{ts}Uncategorized{/ts}
              {/if}
            </td>
            <td class="crm-extensions-usage right">
              {if $row.usage != ''}
                {$row.usage|escape}
              {else}{ts}0{/ts}
              {/if}
            </td>
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
