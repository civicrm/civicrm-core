{*
Display a table of locally-available extensions.

Depends: CRM/common/enableDisableApi.tpl and CRM/common/jsortable.tpl
*}
{if $localExtensionRows}
  <div id="extensions">
    {strip}
    {* handle enable/disable actions*}
    <table id="extensions" class="display">
      <thead>
        <tr>
          <th>{ts}Name{/ts}</th>
          <th>{ts}Status{/ts}</th>
          <th>{ts}Version{/ts}</th>
          <th>{ts}Type{/ts}</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$localExtensionRows key=extKey item=row}
        <tr id="extension-{$row.file|escape}" class="crm-entity crm-extension-{$row.file|escape}{if $row.status eq 'disabled'} disabled{/if}{if $row.status eq 'installed-missing' or $row.status eq 'disabled-missing'} extension-missing{/if}{if $row.status eq 'installed'} extension-installed{/if}">
          <td class="crm-extensions-label">
              <a class="collapsed" href="#"></a>&nbsp;<strong>{$row.label|escape}</strong><br/>{$row.description|escape}
              {if $extAddNewEnabled && array_key_exists($extKey, $remoteExtensionRows) && $remoteExtensionRows[$extKey].upgradelink|smarty:nodefaults}
                <div class="crm-extensions-upgrade">{$remoteExtensionRows[$extKey].upgradelink|smarty:nodefaults}</div>
              {/if}
          </td>
          <td class="crm-extensions-status">{$row.statusLabel} </td>
          <td class="crm-extensions-version">{$row.version|escape}
            {if (!empty($row.develStage) and $row.develStage != 'stable') or preg_match(";(alpha|beta|dev);", $row.version)}
              {icon icon="fa-flask crm-extensions-stage"}{ts}This is a pre-release version. For more details, see the expanded description.{/ts}{/icon}
            {else}
              {icon icon="fa-check-circle crm-extensions-stage"}{ts}This is a stable release version.{/ts}{/icon}
            {/if}
          </td>
          <td class="crm-extensions-description">{$row.type|escape|capitalize}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        <tr class="hiddenElement" id="crm-extensions-details-{$row.file|escape}">
            <td>
                {include file="CRM/Admin/Page/ExtensionDetails.tpl" extension=$row localExtensionRows=$localExtensionRows remoteExtensionRows=$remoteExtensionRows}
            </td>
            <td></td><td></td><td></td><td></td>
        </tr>
        {/foreach}
      </tbody>
    </table>
    {/strip}
  </div>
{else}
  <div class="messages status no-popup">
       {icon icon="fa-info-circle"}{/icon}
      {ts 1="https://civicrm.org/extensions"}There are no extensions to display. Click the "Add New" tab to browse and install extensions posted on the <a href="%1">public CiviCRM Extensions Directory</a>. If you have downloaded extensions manually and don't see them here, try clicking the "Refresh" button.{/ts}
  </div>
{/if}
