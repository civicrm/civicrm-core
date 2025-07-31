<tr id="extension-{$row.file|escape}" class="extension_summary crm-entity crm-extension-{$row.file|escape}{if $row.status eq 'disabled'} disabled{/if}{if $row.status eq 'installed-missing' or $row.status eq 'disabled-missing'} extension-missing{/if}{if $row.status eq 'installed'} extension-installed{/if}">
  <td class="crm-extensions-label">
    <details class="crm-accordion-light">
      <summary>
        <strong>{$row.label|escape}</strong><br/>{$row.description|escape}
        {if $extAddNewEnabled && array_key_exists($extKey, $remoteExtensionRows) && $remoteExtensionRows[$extKey].upgradelink|smarty:nodefaults}
          <div class="crm-extensions-upgrade">{$remoteExtensionRows[$extKey].upgradelink|smarty:nodefaults}</div>
        {/if}
      </summary>
      {include file="CRM/Admin/Page/ExtensionDetails.tpl" extension=$row localExtensionRows=$localExtensionRows remoteExtensionRows=$remoteExtensionRows}
    </details>
  </td>
  <td class="crm-extension-meta">
    {foreach from=$row.authors item=author}
      {$author.name}{if !$author@last}, {/if}
    {/foreach}
  </td>
  <td class="crm-extensions-version right">{$row.version|escape}
    {if !$row.is_stable}
      {icon icon="fa-flask crm-extensions-stage"}{ts}This is a pre-release version. For more details, see the expanded description.{/ts}{/icon}
    {else}
      {icon icon="fa-check-circle crm-extensions-stage"}{ts}This is a stable release version.{/ts}{/icon}
    {/if}
  </td>
  <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
</tr>
