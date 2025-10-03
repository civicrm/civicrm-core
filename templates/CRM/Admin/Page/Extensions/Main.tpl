{*
Display a table of locally-available extensions.
Depends: CRM/common/enableDisableApi.tpl and CRM/common/jsortable.tpl
*}
{if $localExtensionRows}
  <div id="extensions">
    <div id="{$categoryType}-extensions">
      <div class="ui-widget ui-widget-content ui-corner-all" style="margin-bottom: 20px;">
        <h3 class="ui-widget-header ui-corner-top ui-helper-clearfix" style="margin: 0; padding: 8px 12px; font-size: 14px;">
          {ts}Installed{/ts}
          <span class="ui-state-default ui-corner-all crm-extension-count"
                style="float: right; padding: 2px 8px; font-size: 11px; font-weight: normal;background: #666;color: white;border-radius: 10px;">
                {$localExtensionStats.installed + $localExtensionStats.disabled}
          </span>
        </h3>
        <div class="ui-widget-content" style="border-top: none;">
          <div class="ui-widget">
            <h4 class="ui-state-default ui-helper-clearfix"
                style="margin: 0; padding: 6px 20px; font-size: 16px; font-weight: 600; background: #f6f6f6;">
              {ts}Enabled{/ts}
              <span class="ui-state-active ui-corner-all crm-extension-count" style="padding: 2px 8px; font-size: 11px; font-weight: normal; background: #666;color: white;border-radius: 10px;">{$localExtensionStats.installed}</span>
            </h4>
            {* handle enable/disable actions*}
            {strip}
              <table class="ui-widget ui-widget-content display" style="margin: 0; border: none;">
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
        <div class="ui-widget-content" style="border-top: none;">
          <div class="ui-widget">
            <h4 class="ui-state-default ui-helper-clearfix"
                style="margin: 0; padding: 6px 20px; font-size: 16px; font-weight: 600; background: #f6f6f6;">
              {ts}Disabled{/ts}
              <span class="ui-state-active ui-corner-all crm-extension-count" style="padding: 2px 8px; font-size: 11px; font-weight: normal; background: #666;color: white;border-radius: 10px;">{$localExtensionStats.disabled}</span>
            </h4>
            {* handle enable/disable actions*}
            {strip}
              <table class="ui-widget ui-widget-content display" style="margin: 0; border: none;">
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
      <div class="ui-widget ui-widget-content ui-corner-all" style="margin-bottom: 20px;">
        <h3 class="ui-widget-header ui-corner-top ui-helper-clearfix" style="margin: 0; padding: 8px 12px; font-size: 14px;">
          {ts}Uninstalled{/ts}
          <span class="ui-state-default ui-corner-all crm-extension-count" style="padding: 2px 8px; font-size: 11px; font-weight: normal;background: #666;color: white;border-radius: 10px;">{$localExtensionStats.uninstalled}</span>
        </h3>
        <div class="ui-widget-content" style="border-top: none;">
          {* handle enable/disable actions*}
          {strip}
            <table class="ui-widget ui-widget-content display" style="margin: 0; border: none;">
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
