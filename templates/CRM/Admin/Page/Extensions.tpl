{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 32 or $action eq 64}
  {include file="CRM/Admin/Form/Extensions.tpl"}
{else}
  <div class="crm-content-block crm-block">
    {if $action ne 1 and $action ne 2}
      {include file="CRM/Admin/Page/Extensions/Refresh.tpl"}
    {/if}

    {if $extDbUpgrades}
      <div class="messages warning">
        <p>{ts 1=$extDbUpgradeUrl}Your extensions require database updates. Please <a href="%1">execute the updates</a>.{/ts}</p>
      </div>
    {/if}

    {include file="CRM/Admin/Page/Extensions/About.tpl"}

    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}

    <div class="ui-widget ui-widget-content ui-corner-all" style="padding: 4px;">
      <input type="search" id="search_extension" class="ui-widget-content ui-corner-all" placeholder="🔍 {ts escape='htmlattribute'}Search extensions{/ts}" oninput="filterExtensions()" style="padding: 8px;">
    </div>
    <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
      <ul class="crm-extensions-tabs-list" role="tablist">
        <li id="tab_summary" role="tab" class="crm-tab-button">
          <a href="#extensions-main" title="{ts escape='htmlattribute'}Extensions{/ts}">
            <span>{ts}Downloaded Extensions{/ts}</span>
            <i class="crm-i fa-house-circle-check" role="img" aria-hidden="true"></i>
          </a>
        </li>
        <li id="tab_addnew" role="tab" class="crm-tab-button">
          <a href="#extensions-addnew" title="{ts escape='htmlattribute'}Add Reviewed{/ts}">
            <span>{ts}Add Reviewed{/ts}</span>
            <i class="crm-i fa-trophy" role="img" aria-hidden="true"></i>
          </a>
        </li>
        <li id="tab_addother" role="tab" class="crm-tab-button">
          <a href="#extensions-addother" title="{ts escape='htmlattribute'}Add Other{/ts}">
            <span>{ts}Add Unreviewed{/ts}</span>
            <i class="crm-i fa-triangle-exclamation" role="img" aria-hidden="true"></i>
          </a>
        </li>
      </ul>

      <div id="extensions-main" role="tabpanel" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        {include file="CRM/Admin/Page/Extensions/Main.tpl"}
      </div>
      <div id="extensions-addnew" role="tabpanel" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        {if $extAddNewEnabled}
          {if $extAddNewReqs}
            {include file="CRM/Admin/Page/Extensions/AddNewReq.tpl"}
          {else}
            {include file="CRM/Admin/Page/Extensions/AddNew.tpl"}
          {/if}
        {else}
          {ts}The system administrator has disabled this feature.{/ts}
        {/if}
      </div>
      <div id="extensions-addother" role="tabpanel" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
        {if $extAddNewEnabled}
          {if $extAddNewReqs}
            {include file="CRM/Admin/Page/Extensions/AddOtherReq.tpl"}
          {else}
            {include file="CRM/Admin/Page/Extensions/AddOther.tpl"}
          {/if}
        {else}
          {ts}The system administrator has disabled this feature.{/ts}
        {/if}
      </div>

      <div class="clear"></div>
    </div>

    {if $action ne 1 and $action ne 2}
        {include file="CRM/Admin/Page/Extensions/Refresh.tpl"}
    {/if}
  </div>

  {include file="CRM/common/TabHeader.tpl" defaultTab="summary"}

  {* Refresh buttons *}
  {literal}
    <script type="text/javascript">
    CRM.$(function($) {
      $('.crm-extensions-refresh').click(function(event){
        event.stopPropagation();
        CRM.alert('', '{/literal}{ts escape="js"}Refreshing...{/ts}{literal}', 'crm-msg-loading', {expires: 0});
        CRM.api('Extension', 'refresh', {}, {
          'callBack' : function(result){
            if (result.is_error) {
              CRM.alert(result.error_message, '{/literal}{ts escape="js"}Refresh Error{/ts}{literal}', 'error');
            } else {
              window.location.reload();
            }
          }
        });
        return false;
      }); // .click
    }); // onload

    // Keyword search.
    function filterExtensions() {
      const searchTerm = document.getElementById('search_extension').value.toLowerCase().trim();
      const coreTab = document.getElementById('extensions-main');
      const addNewTab = document.getElementById('extensions-addnew');
      const addOtherTab = document.getElementById('extensions-addother');
      // Filter Core Extensions.
      if (coreTab) {
        const coreRows = coreTab.querySelectorAll('.crm-extension-row');
        coreRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
        });
      }
      // Filter Add Reviewed Extensions.
      if (addNewTab) {
        const addNewRows = addNewTab.querySelectorAll('.crm-extension-row');
        addNewRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
        });
      }
      // Filter Add Unreviewed Extensions.
      if (addOtherTab) {
        const addOtherRows = addOtherTab.querySelectorAll('.crm-extension-row');
        addOtherRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
        });
      }
    }
    </script>
  {/literal}
{/if}
