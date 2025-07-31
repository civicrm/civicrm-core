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
    <div>
      <input type="text" id="search_extension" placeholder="Search extensions..." oninput="filterExtensions()">
    </div>
    <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
        <ul class="crm-extensions-tabs-list" role="tablist">
            <li id="tab_summary" role="tab" class="crm-tab-button">
              <a href="#extensions-main" title="{ts escape='htmlattribute'}Core Extensions{/ts}">
              <span> </span> {ts}Core Extensions{/ts}
              <em>&nbsp;</em>
              </a>
            </li>
            <li id="tab_other" role="tab" class="crm-tab-button">
              <a href="#extensions-other" title="{ts escape='htmlattribute'}Other Extensions{/ts}">
              <span> </span> {ts}Other Extensions{/ts}
              <em>&nbsp;</em>
              </a>
            </li>
            <li id="tab_addnew" role="tab" class="crm-tab-button">
              <a href="#extensions-addnew" title="{ts escape='htmlattribute'}Add New{/ts}">
              <span> </span> {ts}Add New{/ts}
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

    // Search functionality
    function filterExtensions() {
      const searchTerm = document.getElementById('search_extension').value.toLowerCase().trim();
      const extensionRows = document.querySelectorAll('.extension_summary');
      extensionRows.forEach(extensionRow => {
        const title = extensionRow.querySelector('summary').textContent.toLowerCase();
        extensionRow.style.display = title.includes(searchTerm) ? 'table-row' : 'none';
      });
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
  .crm-extensions-group {
    margin-bottom: 20px;
  }

  .crm-extensions-group-header {
    background: #f4f4f4;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-bottom: none;
    font-weight: bold;
    margin: 0;
    font-size: 14px;
    color: #333;
  }

  .crm-extensions-group-content {
    border: 1px solid #ddd;
    border-top: none;
  }

  .crm-extensions-subgroup {
    margin: 0;
  }

  .crm-extensions-subgroup-header {
    background: #fafafa;
    padding: 6px 20px;
    border-bottom: 1px solid #eee !important;
    font-size: 16px;
    font-weight: 600;
    color: #666 !important;;
    margin: 0;
  }

  .crm-extensions-subgroup:last-child .crm-extensions-subgroup-header {
    border-bottom: none;
  }

  .crm-extensions-subgroup table {
    margin: 0;
    border: none;
  }

  .crm-extensions-subgroup .crm-extensions-subgroup-header + table {
    border-top: none;
  }

  .crm-extensions-group.empty {
    opacity: 0.7;
  }

  .crm-extensions-group.empty .crm-extensions-group-content {
    padding: 20px;
    text-align: center;
    color: #666;
    font-style: italic;
  }

  .crm-extension-count {
    float: right;
    background: #666;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: normal;
  }
  #extensions {
    /*
    padding-right: 20px;
    padding-left: 20px;
    */
  }

  </style>
    {/literal}
{/if}
