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
      <input type="text" id="search_extension" class="ui-widget-content ui-corner-all" placeholder="🔍{ts escape='htmlattribute'}Search extensions{/ts}" oninput="filterExtensions()" style="padding: 8px; width: 250px; border: 1px solid #aaa;">
    </div>
    <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
        <ul class="crm-extensions-tabs-list" role="tablist">
            <li id="tab_summary" role="tab" class="crm-tab-button">
              <a href="#extensions-main" title="{ts escape='htmlattribute'}Core Extensions{/ts}">
              <span> </span> {ts}Core Extensions{/ts} <span id="core_match_count"></span>
              <em>&nbsp;</em>
              </a>
            </li>
            <li id="tab_other" role="tab" class="crm-tab-button">
              <a href="#extensions-other" title="{ts escape='htmlattribute'}Other Extensions{/ts}">
              <span> </span> {ts}Other Extensions{/ts}<span id="other_match_count"></span>
              <em>&nbsp;</em>
              </a>
            </li>
            <li id="tab_addnew" role="tab" class="crm-tab-button">
              <a href="#extensions-addnew" title="{ts escape='htmlattribute'}Add New{/ts}">
              <span> </span> {ts}Add New{/ts}<span id="new_match_count"></span>
              <em>&nbsp;</em>
              </a>
            </li>
        </ul>

        <div id="extensions-main" role="tabpanel" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
            {include file="CRM/Admin/Page/Extensions/Main.tpl" isCore=TRUE localExtensionStats=$extensionCountByStatusType.core categoryType="core"}
        </div>
        <div id="extensions-other" role="tabpanel" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
            {include file="CRM/Admin/Page/Extensions/Main.tpl" isCore=FALSE localExtensionStats=$extensionCountByStatusType.other categoryType="others"}
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

    // Search functionality with count display
    function filterExtensions() {
      const searchTerm = document.getElementById('search_extension').value.toLowerCase().trim();

      // Get all tabs
      const coreTab = document.getElementById('extensions-main');
      const otherTab = document.getElementById('extensions-other');
      const addNewTab = document.getElementById('extensions-addnew');

      // Initialize counts
      let coreCount = 0;
      let otherCount = 0;
      let addNewCount = 0;

      // Filter Core Extensions
      if (coreTab) {
        const coreRows = coreTab.querySelectorAll('.extension_summary');
        coreRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
          if (isVisible) coreCount++;
        });
      }

      // Filter Other Extensions
      if (otherTab) {
        const otherRows = otherTab.querySelectorAll('.extension_summary');
        otherRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
          if (isVisible) otherCount++;
        });
      }

      // Filter Add New Extensions (if applicable)
      if (addNewTab) {
        const addNewRows = addNewTab.querySelectorAll('.extension_summary');
        addNewRows.forEach(extensionRow => {
          const title = extensionRow.querySelector('summary').textContent.toLowerCase();
          const isVisible = title.includes(searchTerm);
          extensionRow.style.display = isVisible ? 'table-row' : 'none';
          if (isVisible) addNewCount++;
        });
      }

      // Update tab labels with counts
      const coreTabCount = document.querySelector('#core_match_count');
      const otherTabCount = document.querySelector('#other_match_count');
      const addNewTabCount = document.querySelector('#new_match_count');

      if (coreTabCount) {
        if (searchTerm) {
          coreTabCount.textContent = ` (${coreCount})`;
        } else {
          coreTabCount.textContent = '';
        }
      }

      if (otherTabCount) {
        if (searchTerm) {
          otherTabCount.textContent = ` (${otherCount})`;
        } else {
          otherTabCount.textContent = '';
        }
      }

      if (addNewTabCount) {
        if (searchTerm) {
          addNewTabCount.textContent = ` (${addNewCount})`;
        } else {
          addNewTabCount.textContent = '';
        }
      }
    }
    </script>
  <style>
    .crm-container #extensions-other {
      padding: 0;
      border-top: 0;
    }
    .crm-container #extensions-other table {
      box-shadow: none;
      border: 0 solid transparent;
    }
    .crm-container #extensions-other table th {
      background-color: var(--crm-tab-bg-active);
      min-width: max-content;
      white-space: nowrap; /* prevents wrapping of sort icons with oversized descriptions */
    }
    .crm-container #extensions-other table summary {
      padding: 0;
      background-color: unset;
      color: var(--crm-c-text);
      font-weight: unset;
      font-family: var(--crm-font);
    }
  </style>
    {/literal}
{/if}
