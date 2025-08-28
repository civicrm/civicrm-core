{*
Display a table of locally-available extensions.
Depends: CRM/common/enableDisableApi.tpl and CRM/common/jsortable.tpl
*}
{if $localExtensionRows}
  <div id="extensions">
    <div id="{$categoryType}-extensions">
      <div class="crm-extensions-group">
        <h3 class="crm-extensions-group-header">
          {ts}Installed{/ts}
          <span class="crm-extension-count">{$localExtensionStats.installed + $localExtensionStats.disabled}</span>
        </h3>
        <div class="crm-extensions-group-content">
          <div class="crm-extensions-subgroup">
            <h4 class="crm-extensions-subgroup-header">
              {ts}Enabled{/ts}
              <span class="crm-extension-count">{$localExtensionStats.installed}</span>
            </h4>
            {* handle enable/disable actions*}
            {strip}
              <table id="extensions" class="display">
                <thead>
                <tr>
                  <th>{ts}Extension{/ts}</th>
                  <th>{ts}Author{/ts}</th>
                  <th>{ts}Version{/ts}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$localExtensionRows key=extKey item=row}
                  {if $row.is_core eq $isCore and $row.status eq 'installed'}
                    {include file="CRM/Admin/Page/Extensions/MainTableRow.tpl" row=$row extKey=$extKey localExtensionRows=$localExtensionRows remoteExtensionRows=$remoteExtensionRows}
                  {/if}
                {/foreach}
                </tbody>
              </table>
            {/strip}
          </div>
        </div>
        <div class="crm-extensions-group-content">
          <div class="crm-extensions-subgroup">
            <h4 class="crm-extensions-subgroup-header">
              {ts}Disabled{/ts}
              <span class="crm-extension-count">{$localExtensionStats.disabled}</span>
            </h4>
            {* handle enable/disable actions*}
            {strip}
              <table id="extensions" class="display">
                <thead>
                <tr>
                  <th>{ts}Extension{/ts}</th>
                  <th>{ts}Author{/ts}</th>
                  <th>{ts}Version{/ts}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$localExtensionRows key=extKey item=row}
                  {if $row.is_core eq $isCore and $row.status eq 'disabled'}
                    {include file="CRM/Admin/Page/Extensions/MainTableRow.tpl" row=$row extKey=$extKey localExtensionRows=$localExtensionRows remoteExtensionRows=$remoteExtensionRows}
                  {/if}
                {/foreach}
                </tbody>
              </table>
            {/strip}
          </div>
        </div>
      </div>
      <div class="crm-extensions-group">
        <h3 class="crm-extensions-group-header">
          <span>{ts}Uninstalled{/ts}</span>
          <span class="crm-extension-count">{$localExtensionStats.uninstalled}</span>
        </h3>
        <div class="crm-extensions-group-content">
          {* handle enable/disable actions*}
          {strip}
            <table id="extensions" class="display">
              <thead>
              <tr>
                <th>{ts}Extension{/ts}</th>
                <th>{ts}Author{/ts}</th>
                <th>{ts}Version{/ts}</th>
                <th></th>
              </tr>
              </thead>
              <tbody>
              {foreach from=$localExtensionRows key=extKey item=row}
                {if $row.is_core eq $isCore and $row.status eq 'uninstalled'}
                  {include file="CRM/Admin/Page/Extensions/MainTableRow.tpl" row=$row extKey=$extKey localExtensionRows=$localExtensionRows remoteExtensionRows=$remoteExtensionRows}
                {/if}
              {/foreach}
              </tbody>
            </table>
          {/strip}
        </div>
      </div>
    </div>
  </div>
{else}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts 1="https://civicrm.org/extensions"}There are no extensions to display. Click the "Add New" tab to browse and install extensions posted on the <a href="%1">public CiviCRM Extensions Directory</a>. If you have downloaded extensions manually and don't see them here, try clicking the "Refresh" button.{/ts}
  </div>
{/if}
